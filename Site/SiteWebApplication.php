<?php

require_once 'Site/SiteApplication.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/pages/SiteXhtmlExceptionPage.php';

/**
 * Base class for a web application
 *
 * Web-applicaitions are set up to resolve pages and handle page requests.
 *
 * @package   Site
 * @copyright 2006-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteWebApplication extends SiteApplication
{
	// {{{ protected properties

	/**
	 * The base value for all of this application's anchor hrefs
	 *
	 * @var string
	 */
	protected $base_uri = null;

	/**
	 * The base value for all of this application's anchor hrefs over secure
	 * connections
	 *
	 * @var string
	 */
	protected $secure_base_uri = null;

	/**
	 * Whether or not this application is loaded over a secure connection
	 *
	 * @var boolean
	 */
	protected $secure = false;

	/**
	 * The uri of the current page request
	 *
	 * @var string
	 */
	protected $uri = null;

	/**
	 * The current page of this application
	 *
	 * @var SitePage
	 */
	protected $page = null;

	// }}}

	// {{{ public static function cleanUriGetVar()

	/**
	 * Removes a get var from the uri, and returns the cleaned version.
	 *
	 * @param string $uri the uri to clean.
	 * @param string $id the get var id to clean.
	 * @param string $value the value of the var to clean.
	 *
	 * @return string $uri the cleaned uri.
	 */
	public static function cleanUriGetVar($uri, $id, $value)
	{
		$regexp = sprintf(
			'/
			(?:
				(\?)%1$s=%2$s& # get var starts query string
				|
				\?%1$s=%2$s$   # get var is entire query string
				|
				(&)%1$s=%2$s&  # get var is embedded in query string
				|
				&%1$s=%2$s$    # get var ends query string
			)
			/xu',
			preg_quote($id, '/'),
			preg_quote($value, '/'));

		$uri = preg_replace($regexp, '\1\2', $uri);

		return $uri;
	}

	// }}}
	// {{{ public function run()

	/**
	 * Runs this web application
	 */
	public function run()
	{
		$this->initModules();
		$this->parseUri();

		$page_data = false;
		$cache_key = false;
		$cached = false;

		if ($this->config->memcache->page_cache &&
			$this->hasModule('SiteMemcacheModule')) {

			$memcache = $this->getModule('SiteMemcacheModule');

			// set user as active if posting data
			if (!empty($_POST)) {
				$key = $this->getPageCacheUserIdentifier();
				$memcache->setNs('page-cache', $key, true,
					$this->getPageCacheTimeout());
			}

			// check if request is cacheable
			if ($this->isRequestPageCacheable()) {
				$cache_key = $this->getPageCacheKey();
				$page_data = $memcache->getNs('page-cache', $cache_key);
				$cached = ($page_data !== false);
			}
		}

		try {
			if (!$cached) {
				$page_data = array();

				$this->loadPage();
				$this->page->layout->init();
				$this->page->init();
				$this->page->layout->process();
				$this->page->process();
				$this->page->layout->build();
				$this->page->build();
				$this->page->layout->finalize();
				$this->page->finalize();
				$this->page->layout->complete();

				// get page content
				ob_start();
				$this->page->layout->display();
				$page_data['content'] = ob_get_clean();

				if ($cache_key !== false) {
					// get headers
					$page_data['headers'] = headers_list();

					// cache page data
					$memcache->setNs('static-content', $cache_key, $page_data,
						$this->getPageCacheTimeout());
				}
			}

			// send cached page headers
			if ($cached) {
				foreach ($page_data['headers'] as $header) {
					header($header);
				}
			}

			// display page content
			echo $page_data['content'];

		} catch (Exception $e) {
			$this->loadExceptionPage();

			if ($this->page instanceof SiteExceptionPage) {
				$this->page->setException($e);
			}

			$this->page->layout->init();
			$this->page->init();
			$this->page->layout->build();
			$this->page->build();
			$this->page->layout->finalize();
			$this->page->finalize();
			$this->page->layout->complete();

			// display exception page (never cached)
			$this->page->layout->display();
		}
	}

	// }}}
	// {{{ protected function parseUri()

	/**
	 * Initializes the base href and URI from the request URI
	 */
	protected function parseUri()
	{
		$this->secure = isset($_SERVER['HTTPS']);

		// check for session module
		if (isset($this->session) &&
			$this->session instanceof SiteSessionModule &&
			$this->session->isActive()) {

			// remove session ID from URI since it will be added back where
			// necessary
			$regexp = sprintf('/%s=[^&]*&?/u',
				preg_quote($this->session->getSessionName(), '/'));

			$this->uri = preg_replace($regexp, '', $_SERVER['REQUEST_URI']);
		} else {
			$this->uri = $_SERVER['REQUEST_URI'];
		}

		// if base URI starts with a forward-slash, it might be an SVN
		// working-copy on the staging server
		if (substr($this->base_uri, 0, 1) == '/') {
			// check for '/trunk/' in the base URI and replace with the current
			// working directory if found; also allow for instance names in the
			// working-copy URIs
			$base_uri = preg_quote($this->base_uri, '|');
			$base_uri = str_replace('/trunk/', '-?[^/]*/[^/]*/', $base_uri, $count);
			if ($count == 1) {
				$regexp = sprintf('|%s|u', $base_uri);
				if (preg_match($regexp, $this->uri, $matches)) {
					$this->base_uri = $matches[0];
					$this->secure_base_uri = $matches[0];
				}
			}
		}

		// Not all Site's use SiteImage, so only set the cdn base if it exists.
		if (class_exists('SiteImage')) {
			SiteImage::$cdn_base = $this->getCdnBase();
		}
	}

	// }}}
	// {{{ protected function getCdnBase()

	/**
	 * Gets the base cdn
	 *
	 * @param boolean $secure whether or not the base cdn should be a secure
	 *                         URI. The default value of null maintains the
	 *                         same security as the current page.
	 *
	 * @return string the base cdn or null if cdn settings aren't set.
	 */
	protected function getCdnBase($secure = null)
	{
		$cdn_base = null;

		if ($this->config->uri->cdn_base != '' &&
			$this->config->uri->secure_cdn_base != '') {

			if ($secure === null) {
				$secure = $this->isSecure();
			}

			$cdn_base = ($secure === true) ?
				$this->config->uri->secure_cdn_base :
				$this->config->uri->cdn_base;
		}

		return $cdn_base;
	}

	// }}}

	// static caching methods
	// {{{ protected function isRequestPageCacheable()

	/**
	 * Gets whether or not this entire page request can be cached in the page
	 * cache
	 *
	 * By default, page caching is disabled in the application config.
	 * Additionally, page requests must have no active session and no HTTP
	 * post data to be cacheable.
	 *
	 * @return boolean true if the current request is cacheable, otherwise
	 *                 false.
	 */
	protected function isRequestPageCacheable()
	{
		$config = $this->config->memcache;

		$session_is_active = ($this->hasModule('SiteSessionModule') &&
			$this->getModule('SiteSessionModule')->isActive());

		return ($config->page_cache && !$session_is_active && empty($_POST) &&
			!$this->isPageCacheUserActive());
	}

	// }}}
	// {{{ protected function isPageCacheUserActive()

	protected function isPageCacheUserActive()
	{
		$active = false;

		if ($this->hasModule('SiteMemcacheModule')) {
			$memcache = $this->getModule('SiteMemcacheModule');
			$key = $this->getPageCacheUserIdentifier();
			$active = $memcache->getNs('static-content', $key);
		}

		return $active;
	}

	// }}}
	// {{{ protected function getPageCacheExpirationTime()

	/**
	 * Gets the expiration time for the page cache
	 *
	 * The timeout is specified in the application config. By default, the
	 * exipration time is 15 minutes in the future.
	 *
	 * @return integer the expiration time for the page cache.
	 */
	protected function getPageCacheExpirationTime()
	{
		$config = $this->app->config->memcache;

		// default to 15 minutes in the future
		$timeout = ($config->page_cache_timeout === null) ?
			900 : $config->page_cache_timeout;

		return time() + $timeout;
	}

	// }}}
	// {{{ protected function getPageCacheKey()

	/**
	 * Gets the cache key used for the page cache
	 *
	 * This is based on the application identifier and the current request
	 * URI.
	 *
	 * @return string the cache key used for the page cache.
	 */
	protected function getPageCacheKey()
	{
		return $this->id.'-page-'.$this->getUri();
	}

	// }}}
	// {{{ protected function getPageCacheUserIdentifier()

	protected function getPageCacheUserIdentifier()
	{
		$hash = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
		return 'user-'.$hash;
	}

	// }}}

	// page methods
	// {{{ public function getPage()

	/**
	 * Gets the currently loaded page of this application
	 */
	public function getPage()
	{
		return $this->page;
	}

	// }}}
	// {{{ public function replacePage()

	/**
	 * Replaces the current page with another page given a source
	 *
	 * This method can be used to load another page to replace the current
	 * page. For example, this is used to load a confirmation page when
	 * processing an admin index page.
	 *
	 * @param string $source the source of the page with which to replace the
	 *                        current page. The source will be passed to the
	 *                        {@link SiteWebApplication::resolvePage()} method.
	 *
	 * @return SitePage the new page that replaced the current page.
	 *
	 * @see SiteWebApplication::getReplacementPage()
	 */
	public function replacePage($source)
	{
		$new_page = $this->getReplacementPage($source);
		$this->setPage($new_page);
		return $new_page;
	}

	// }}}
	// {{{ public function getReplacementPage()

	/**
	 * Gets a page object to replaces the current page given a source
	 *
	 * This method can be used to instantiate another page to replace the
	 * current page. For example, this is used to load a confirmation page when
	 * processing an admin index page. The returned page object should be passed
	 * to {@link SiteWebApplication::setPage()} to actually use it. Use
	 * {@link SiteWebApplication::replacePage()} instead of this method if
	 * access to the page object before init() is run on it is not necessary.
	 *
	 * @param string $source the source of the page with which to replace the
	 *                        current page. The source will be passed to the
	 *                        {@link SiteWebApplication::resolvePage()} method.
	 *
	 * @return SitePage the replacement page.
	 *
	 * @see SiteWebApplication::replacePage()
	 */
	public function getReplacementPage($source)
	{
		$source = $this->normalizeSource($source);
		$this->checkSecure($source);
		$new_page = $this->resolvePage($source);

		return $new_page;
	}

	// }}}
	// {{{ public function setPage()

	/**
	 * Sets the current page
	 *
	 * If a page object is provided, the current page is set to the provided
	 * page replacing any previous page. This can be useful to process one page
	 * then load another page to process and display.
	 *
	 * If no page object is provided a default page is chosen based on
	 * application specific code. Subclasses should implement logic here to
	 * decide which page sub-class to instantiate.
	 *
	 * @param SiteAbstractPage $page the page to load as a replacement of the current
	 *                        page.
	 *
	 * @see SiteAbstractPage
	 */
	public function setPage(SiteAbstractPage $page)
	{
		$this->page = $page;
		$this->page->layout->init();
		$this->page->init();
	}

	// }}}
	// {{{ protected function normalizeSource()

	/**
	 * Normalizes a source string
	 *
	 * Normalized source strings never end with a slash.
	 *
	 * @param string $source the source string to normalize.
	 *
	 * @return string the normalized source string.
	 */
	protected function normalizeSource($source)
	{
		$path = explode('/', $source);

		// remove trailing slash
		if (end($path) == '')
			array_pop($path);

		// normalize $source, never end with a slash
		$source = implode('/', $path);

		return $source;
	}

	// }}}
	// {{{ protected function explodeSource()

	protected function explodeSource($source)
	{
		if ($source == '')
			$path = array();
		else
			$path = explode('/', $source);

		return $path;
	}

	// }}}
	// {{{ protected function loadPage()

	/**
	 * Loads the page for the current request into this application
	 */
	protected function loadPage()
	{
		if ($this->page === null) {
			$source = $this->normalizeSource(self::initVar('source'));
			$this->checkSecure($source);
			$this->page = $this->resolvePage($source);
		}
	}

	// }}}
	// {{{ protected function loadExceptionPage()

	protected function loadExceptionPage()
	{
		$source = $this->normalizeSource(self::initVar('source'));
		$this->page = $this->resolveExceptionPage($source);
	}

	// }}}
	// {{{ protected function resolvePage()

	/**
	 * Resolves a page for a particular source
	 *
	 * Sub-classes are encouraged to override this method to create different
	 * page instances for different sources.
	 *
	 * @param string $source the source to use to resolve the page.
	 *
	 * @return SitePage the page corresponding the given source.
	 */
	protected function resolvePage($source)
	{
		$layout = $this->resolveLayout($source);
		return new SitePage($this, $layout);
	}

	// }}}
	// {{{ protected function resolveExceptionPage()

	/**
	 * Resolves an exception page for a particular source
	 *
	 * Sub-classes are encouraged to override this method to create different
	 * exception page instances for different sources.
	 *
	 * @param string $source the source to use to resolve the exception page.
	 *
	 * @return SitePage the exception page corresponding the given source.
	 */
	protected function resolveExceptionPage($source)
	{
		$layout = $this->resolveLayout($source);
		return new SiteXhtmlExceptionPage($this, $layout);
	}

	// }}}
	// {{{ protected function resolveLayout()

	/**
	 * Resolves a layout for a particular source
	 *
	 * Sub-classes are encouraged to override this method to create different
	 * layouts instances for different sources. Most often, applications
	 * resolve few different layouts and many different pages.
	 *
	 * @param string $source the source to use to resolve the layout.
	 *
	 * @return SiteLayout The layout corresponding the given source or null if the
	 *                   default layout is to be used.
	 *
	 * @see SiteWebApplication::resolvePage()
	 */
	protected function resolveLayout($source)
	{
		return null;
	}

	// }}}

	// URI methods
	// {{{ public function setBaseUri()

	/**
	 * Sets the base URI
	 */
	public function setBaseUri($uri)
	{
		$this->base_uri = $uri;
	}

	// }}}
	// {{{ public function setSecureBaseUri()

	/**
	 * Sets the base URI for secure pages
	 */
	public function setSecureBaseUri($uri)
	{
		$this->secure_base_uri = $uri;
	}

	// }}}
	// {{{ public function relocate()

	/**
	 * Relocates to another URI
	 *
	 * Calls the PHP header() function to relocate this application to another
	 * URI. This function does not return and in fact calls the PHP exit()
	 * function to be sure execution does not continue.
	 *
	 * @param string $uri the URI to relocate to.
	 * @param boolean $secure optional. Whether or not the base href should be
	 *                         a secure URI. The default value of null
	 *                         maintains the same security as the current page.
	 *                         This parameter only has an effect if the
	 *                         <i>$uri</i> is relative.
	 * @param boolean $append_sid optional. Whether or not to append the
	 *                             session identifier to the URI. If null, this
	 *                             is determined automatically by the
	 *                             {@link SiteSessionModule::appendSessionId()}
	 *                             method.
	 * @param boolean $permanent Whether or not to the relocate is permanent.
	 *                            Set true for urls that are permanently
	 *                            moved.
	 */
	public function relocate($uri, $secure = null, $append_sid = null,
		$permanent = false)
	{
		// check for session module
		if (isset($this->session) &&
			$this->session instanceof SiteSessionModule)
				$uri = $this->session->appendSessionId($uri, $append_sid);

		if (substr($uri, 0, 1) != '/' && strpos($uri, '://') === false)
			$uri = $this->getBaseHref($secure).$uri;

		if ($permanent)
			header('HTTP/1.1 301 Moved Permanently');

		header('Location: '.$uri);
		exit();
	}

	// }}}
	// {{{ public function getUri()

	/**
	 * Gets the URI of the current page request
	 *
	 * @return string the URI of the current page request.
	 */
	public function getUri()
	{
		return $this->uri;
	}

	// }}}
	// {{{ public function isSecure()

	/**
	 * Whether the current page is being accessed securely
	 *
	 * @return boolean whether the current page access is secure.
	 */
	public function isSecure()
	{
		return $this->secure;
	}

	// }}}
	// {{{ public function getBaseHref()

	/**
	 * Gets the base value for all application anchor hrefs
	 *
	 * @param boolean $secure whether or not the base href should be a secure
	 *                         URI. The default value of null maintains the
	 *                         same security as the current page.
	 *
	 * @return string the base value for all application anchor hrefs.
	 */
	public function getBaseHref($secure = null)
	{
		$base_href = $this->getRootBaseHref($secure);

		if (isset($this->mobile)) {
			if ($this->mobile->isMobileUrl() &&
				$this->mobile->getPrefix() !== null) {

				$base_href.= $this->mobile->getPrefix().'/';
			}
		}

		return $base_href;
	}

	// }}}
	// {{{ public function getBaseCdnHref()

	/**
	 * Gets the base value for all application cdn anchor hrefs
	 *
	 * If the cdn uri settings aren't set up, we fall back to the default base
	 * anchor values. This allows us to fall back to local images if cdn
	 * settings get turned off.
	 *
	 * @param boolean $secure whether or not the base cdn href should be a
	 *                         secure URI. The default value of null maintains
	 *                         the same security as the current page.
	 *
	 * @return string the base value for all application cdn anchor hrefs.
	 */
	public function getBaseCdnHref($secure = null)
	{
		$base_cdn_href = $this->getCdnBase($secure);
		if ($base_cdn_href === null) {
			$base_cdn_href = $this->getBaseHref($secure);
		}

		return $base_cdn_href;
	}

	// }}}
	// {{{ public function getBaseHrefRelativeUri()

	/**
	 * Gets the URI relative to the base href
	 *
	 * @param boolean $secure whether or not the base href should be a secure
	 *                         URI. The default value of null maintains the
	 *                         same security as the current page.
	 *
	 * @return string the relative URI
	 */
	public function getBaseHrefRelativeUri($secure = null)
	{
		if ($secure === null)
			$secure = $this->secure;

		$base_uri = $this->secure ? $this->secure_base_uri : $this->base_uri;
		$protocol = $this->getProtocol();
		$protocol_length = strlen($protocol);

		if (strncmp($base_uri, $protocol, $protocol_length) === 0) {
			$pos = strpos($base_uri, '/', $protocol_length);

			if ($pos !== false)
				$base_uri = substr($base_uri, $pos);
		}

		$base_uri_length = strlen($base_uri);
		if (strncmp($base_uri, $this->uri, $base_uri_length) === 0)
			$uri = substr($this->uri, $base_uri_length);
		else
			$uri = $this->uri;


		// trim mobile prefix from beginning of relative uri
		if (isset($this->mobile) && $this->mobile->isMobileUrl() &&
				$this->mobile->getPrefix() !== null) {

			$uri = substr($uri, strlen($this->mobile->getPrefix()) + 1);
		}

		return $uri;
	}

	// }}}
	// {{{ public function getSwitchMobileLink()

	/**
	 * Gets the link to switch to the mobile, or non-mobile url
	 *
	 * @param boolean $mobile If true, the link is for the mobile version
	 *                        of the site, if false, for the non-mobile version.
	 * @param string $source  Optional additional source path to append tot the
	 *                        base link.
	 *
	 * @return string the link to switch the mobile url of the site
	 */
	public function getSwitchMobileLink($mobile = true, $source = null)
	{
		$link = $this->getRootBaseHref();

		if (!isset($this->mobile)) {
			throw new SwatException(
				'This site does not have a SiteMobileModule');
		}

		if ($mobile && $this->mobile->getPrefix() !== null) {
			$link.'/'.$this->mobile->getPrefix();
		}

		if ($source !== null) {
			$link.= $source;
		}

		$link.= sprintf('?%s=%s',
			$this->mobile->getSwitchGetVar(),
			$mobile ? '1' : '0');

		return $link;
	}

	// }}}
	// {{{ protected function getRootBaseHref()

	/**
	 * Gets the root part of the base-href (usually the protocol and domain)
	 *
	 * @param boolean $secure whether or not the base href should be a secure
	 *                         URI. The default value of null maintains the
	 *                         same security as the current page.
	 *
	 * @return string the root base href.
	 */
	protected function getRootBaseHref($secure = null)
	{
		if ($secure === null)
			$secure = $this->secure;

		if ($secure)
			$base_uri = $this->secure_base_uri;
		else
			$base_uri = $this->base_uri;

		if (substr($base_uri, 0, 1) == '/')
			$base_href = $this->getProtocol($secure).
				$this->getServerName($secure).$base_uri;
		else
			$base_href = $base_uri;

		return $base_href;
	}

	// }}}
	// {{{ protected function getSecureSourceList()

	/**
	 * Gets the list of pages sources that should be secure
	 *
	 * The list of page sources is an array of source strings.
	 * Entries are regular expressions.
	 *
	 * @return array the list of sources that should be secure.
	 */
	protected function getSecureSourceList()
	{
		return array();
	}

	// }}}
	// {{{ protected function checkSecure()

	/**
	 * Checks if this page should be redirected in/out of SSL
	 *
	 * @param string The source string of this page.
	 */
	protected function checkSecure($source)
	{
		foreach ($this->getSecureSourceList() as $pattern) {
			$pattern = str_replace('|', '\|', $pattern);
			$regexp = '|'.$pattern.'|u';
			if (preg_match($regexp, $source) === 1) {
				if ($this->secure) {
					// regenerate the session ID after entering SSL
					if ($this->hasSession() && isset($this->session->_regenerate_id)) {
						$this->session->regenerateId();
						unset($this->session->_regenerate_id);
					}
					return;
				} else {
					$new_uri = $this->getAbsoluteUri(true);

					// set a flag to regenerate session ID on next request when we'll be in SSL
					if ($this->hasSession())
						$this->session->_regenerate_id = true;

					$this->relocate($new_uri, null, true);
				}
			}
		}
	}

	// }}}
	// {{{ protected function getAbsoluteUri()

	protected function getAbsoluteUri($secure = null)
	{
		$base_href = $this->getBaseHref($secure);
		$relative_uri = $this->getBaseHrefRelativeUri($secure);
		$uri = $base_href.$relative_uri;

		return $uri;
	}

	// }}}
	// {{{ protected function getQueryString()

	/**
	 * Gets the query string of the request
	 *
	 * @return string the query string
	 */
	protected function getQueryString()
	{
		$query_string = $_SERVER['QUERY_STRING'];

		if ($query_string == '')
			$query_string = null;

		return $query_string;
	}

	// }}}
	// {{{ protected function getServerName()

	/**
	 * Gets the servername
	 *
	 * @return string the servername
	 */
	protected function getServerName($secure = null)
	{
		$server_name = $_SERVER['HTTP_HOST'];

		if ($secure !== null && $this->secure !== $secure) {
			/* Need to mangle servername for browsers tunnelling on
			 * non-standard ports.
			 */
			$regexp = '/localhost:[0-9]+/u';

			if (preg_match($regexp,  $server_name)) {
				if ($secure)
					$server_name = 'localhost:8443';
				else
					$server_name = 'localhost:8080';
			}
		}

		return $server_name;
	}

	// }}}
	// {{{ protected function getProtocol()

	/**
	 * Gets the protocol
	 *
	 * @return string the protocol
	 */
	protected function getProtocol($secure = null)
	{
		if ($secure === null)
			$secure = $this->secure;

		if ($secure)
			$protocol = 'https://';
		else
			$protocol = 'http://';

		return $protocol;
	}

	// }}}
	// {{{ private function hasSession()

	private function hasSession()
	{
		// check for session module
		return (isset($this->session) &&
			$this->session instanceof SiteSessionModule &&
			$this->session->isActive());
	}

	// }}}
}

?>
