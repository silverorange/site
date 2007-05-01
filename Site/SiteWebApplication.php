<?php

require_once 'Site/SiteApplication.php';
require_once 'Site/pages/SitePage.php';

/**
 * Base class for a web application
 *
 * Web-applicaitions are set up to resolve pages and handle page requests.
 *
 * @package   Site
 * @copyright 2004-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteWebApplication extends SiteApplication
{
	// {{{ public properties

	/**
	 * Source of the exception page
	 *
	 * @var string
	 */
	public $exception_page_source = 'exception';

	// }}}
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

	// {{{ public function run()

	/**
	 * Runs this web application
	 */
	public function run()
	{
		$this->initModules();
		$this->parseURI();

		try {
			$this->loadPage();
			$this->page->layout->init();
			$this->page->init();
			$this->page->layout->process();
			$this->page->process();
			$this->page->layout->build();
			$this->page->build();
			$this->page->finalize();
			$this->page->layout->finalize();
		} catch (Exception $e) {
			$this->replacePage($this->exception_page_source);

			if ($this->page instanceof SiteExceptionPage)
				$this->page->setException($e);

			$this->page->layout->build();
			$this->page->build();
			$this->page->finalize();
			$this->page->layout->finalize();
		}

		$this->page->layout->display();
	}

	// }}}
	// {{{ protected function parseURI()

	/**
	 * Initializes the base href and URI from the request URI
	 */
	protected function parseURI()
	{
		$this->secure = isset($_SERVER['HTTPS']);

		// check for session module
		if (isset($this->session) &&
			$this->session instanceof SiteSessionModule &&
			$this->session->isActive()) {

			// remove session ID from URI since it will be added back where necessary
			$regexp = sprintf('/%s=[^&]*&?/u',
				preg_quote($this->session->getSessionName(), '/'));

			$this->uri = preg_replace($regexp, '', $_SERVER['REQUEST_URI']);
		} else {
			$this->uri = $_SERVER['REQUEST_URI'];
		}

		if (substr($this->base_uri, 0, 1) == '/') {
			$regexp = sprintf('|%s|u', $this->base_uri);
			if (preg_match($regexp, $this->uri, $matches)) {
				$this->base_uri = $matches[0];
				$this->secure_base_uri = $matches[0];
			} 
		}
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
	 * @see SiteWebApplication::getReplacementPage()
	 */
	public function replacePage($source)
	{
		$new_page = $this->getReplacementPage($source);
		$this->setPage($new_page);
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
	 * @param SitePage $page the page to load as a replacement of the current
	 *                        page.
	 *
	 * @see SitePage
	 */
	public function setPage(SitePage $page)
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
		if (strlen(end($path)) == 0)
			array_pop($path);

		// normalize $source, never end with a slash
		$source = implode('/', $path);

		return $source;
	}

	// }}}
	// {{{ protected function explodeSource()

	protected function explodeSource($source)
	{
		if (strlen($source) === 0)
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
	 * @return SitePage the page corresponding the given layout or null if the
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
	 * function just to be sure execution does not continue.
	 *
	 * @param string $uri the URI to relocate to.
	 * @param boolean $secure whether or not the base href should be a secure
	 *                         URI. The default value of null maintains the
	 *                         same security as the current page.
	 * @param boolean $append_sid whether to append sid to URI. If null, this
	 *                             is determined internally.
	 */
	public function relocate($uri, $secure = null, $append_sid = null)
	{
		// check for session module
		if (isset($this->session) &&
			$this->session instanceof SiteSessionModule)
				$uri = $this->session->appendSessionId($uri, $append_sid);

		if (substr($uri, 0, 1) != '/' && strpos($uri, '://') === false)
			$uri = $this->getBaseHref($secure).$uri;

		header('Location: '.$uri);
		exit();

		parent::relocate($uri, $secure);
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
		// never relocate an exception page
		if ($source === $this->exception_page_source)
			return;

		foreach ($this->getSecureSourceList() as $pattern) {
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
	// {{{ protected function getBaseHrefRelativeUri()

	protected function getBaseHrefRelativeUri($secure = null)
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

		return $uri;
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

		if (strlen($query_string) === 0)
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
