<?php

require_once 'Date/TimeZone.php';
require_once 'Site/exceptions/SiteException.php';
require_once 'Site/SiteObject.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/SiteApplicationModule.php';
require_once 'Swat/SwatDate.php';

/**
 * Base class for a web application
 *
 * @package   Site
 * @copyright 2004-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteApplication extends SiteObject
{
	// {{{ constants

	const VAR_POST    = 1;
	const VAR_GET     = 2;
	const VAR_REQUEST = 4;
	const VAR_COOKIE  = 8;
	const VAR_SERVER  = 16;
	const VAR_SESSION = 32;
	const VAR_FILES   = 64;
	const VAR_ENV     = 128;

	// }}}
	// {{{ public properties

	/**
	 * A unique identifier for this application
	 *
	 * @var string
	 */
	public $id;
	public $exception_page_source = 'exception';

	/**
	 * Default time zone
	 *
	 * This time zone may be used to display dates that have no time zone
	 * information.
	 *
	 * Time zones are specified as {@link Date_TimeZone} objects and it is
	 * recommended to use the continent/city time zone format. For example,
	 * if this application is based in Halifax, Canada, use 'America/Halifax'
	 * as the time zone.
	 *
	 * If unspecified, the default time zone is set to 'UTC'.
	 *
	 * @var Date_TimeZone
	 */
	public $default_time_zone = null;

	// }}}
	// {{{ protected properties

	/**
	 * The base value for all of this application's anchor hrefs
	 *
	 * @var string
	 */
	protected $base_uri = null;
	protected $secure_base_uri = null;
	protected $secure = false;

	/**
	 * The uri of the current page request
	 *
	 * @var string
	 */
	protected $uri = null;

	/**
	 * Application modules
	 *
	 * Application modules are pieces of code that add specific functionality
	 * to an application such as database connectivity, session handling or
	 * configuration.
	 *
	 * This is an associative array of modules loaded in this application. The
	 * array is of the form 'module identifier' => 'module'.
	 *
	 * @var array
	 * @see SiteApplication::getDefaultModuleList(),
	 *      SiteApplication::addModule()
	 */
	protected $modules = array();

	/**
	 * The current page of this application
	 *
	 * @var SitePage
	 */
	protected $page = null;

	// }}}

	// {{{ public function __construct()

	/**
	 * Creates a new Site application
	 *
	 * When the application is created, the default modules are loaded. See
	 * {@link SiteApplication::getDefaultModuleList()}.
	 *
	 * @param string $id a unique identifier for this application.
	 */
	public function __construct($id)
	{
		$this->id = $id;

		// load default modules
		foreach ($this->getDefaultModuleList() as $module_id => $module_class)
			$this->addModule(new $module_class($this), $module_id);

		$this->default_time_zone = new Date_TimeZone('UTC');
	}

	// }}}
	// {{{ public function init()

	/**
	 * Initializes the application without running it
	 */
	public function init()
	{
		$this->initModules();
	}

	// }}}
	// {{{ public function run()

	/**
	 * Run the application.
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
			$this->page->layout->finalize();
		} catch (Exception $e) {
			$this->replacePage($this->exception_page_source);

			if ($this->page instanceof SiteExceptionPage)
				$this->page->setException($e);

			$this->page->layout->build();
			$this->page->build();
			$this->page->layout->finalize();
		}

		$this->page->layout->display();
	}

	// }}}
	// {{{ protected function parseURI()

	/**
	 * Initializes the base href and URI
	 */
	protected function parseURI()
	{
		$this->secure = isset($_SERVER['HTTPS']);
		$this->uri = $_SERVER['REQUEST_URI'];

		if (substr($this->base_uri, 0, 1) == '/') {
			$regexp = sprintf('|%s|u', $this->base_uri);
			if (preg_match($regexp, $this->uri, $matches)) {
				$this->base_uri = $matches[0];
				$this->secure_base_uri = $matches[0];
			} 
		}
	}

	// }}}

	// module methods
	// {{{ public function addModule()

	/**
	 * Adds a module to this application
	 *
	 * @param SiteApplicationModule $module the module to add to this
	 *                                       applicaiton.
	 * @param string $id an identifier for this module. If a module with the
	 *                    given identifier already exists in this application,
	 *                    an exception is thrown.
	 */
	public function addModule(SiteApplicationModule $module, $id)
	{
		if (isset($this->modules[$id]))
			throw new SiteException("A module with the identifier '{$id}' ".
				'already exists in this applicaiton.');

		$properties = get_object_vars($this);
		if (array_key_exists($id, $properties))
			throw new SiteException("Invalid module identifier '{$id}'. ".
				'Module identifiers must not be the same as any of the '.
				'property names of this application object.');

		$this->modules[$id] = $module;
	}

	// }}}
	// {{{ protected function initModules()

	/**
	 * Initializes the modules currently loaded by this application
	 */
	protected function initModules()
	{
		foreach ($this->modules as $module)
			$module->init();
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this application
	 *
	 * The list of modules is an associative array of the form
	 * 'module identifier' => 'module class name'. After instantiation, loaded
	 * modules are accessible as read-only properties of this application.
	 * The public property names correspond directly to the module identifiers
	 * specified in the module list array.
	 *
	 * The modules are loaded in the order they are specified in this list. If
	 * there are module dependencies, the order of the returned array should
	 * reflect this.
	 *
	 * No modules are loaded by default. Subclasses of SiteApplication may
	 * specify their own list of modules to load by overriding this method.
	 *
	 * @return array the list of modules to load for this application.
	 */
	protected function getDefaultModuleList()
	{
		return array();
	}

	// }}}
	// {{{ private function __get()

	private function __get($name)
	{
		if (isset($this->modules[$name]))
			return $this->modules[$name];

		throw new SiteException('Application does not have a property with '.
			"the name '{$name}', and no application module with the ".
			"identifier '{$name}' is loaded.");
	}

	// }}}
	// {{{ private function __isset()

	private function __isset($name)
	{
		$isset = isset($this->$name);
		if (!$isset)
			$isset = isset($this->modules[$name]);

		return $isset;
	}

	// }}}

	// page methods
	// {{{ public function getPage()

	/**
	 * Gets the current page
	 */
	public function getPage()
	{
		return $this->page;
	}

	// }}}
	// {{{ public function replacePage()

	/**
	 * Replace the page object
	 *
	 * This method can be used to load another page to replace the current 
	 * page. For example, this is used to load a confirmation page when 
	 * processing an admin index page.
	 */
	public function replacePage($source)
	{
		$source = $this->normalizeSource($source);
		$this->checkSecure($source);
		$new_page = $this->resolvePage($source);
		$this->setPage($new_page);
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
	 * @param SitePage the page to load as a replacement of the current page.
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
	// {{{ protected function loadPage()

	/**
	 * Loads the page
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
	 * @return SitePage An instance of a SitePage is returned.
	 */
	protected function resolvePage($source)
	{
		$layout = $this->resolveLayout($source);
		return new SitePage($this, $layout);
	}

	// }}}
	// {{{ protected function resolveLayout()

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
				$uri = $this->session->appendSessionID($uri, $append_sid);

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
					return;
				} else {
					$new_uri = $this->getAbsoluteUri(true);
					$this->relocate($new_uri, null, true);
				}
			}
		}

		if ($this->secure) {
			$new_uri = $this->getAbsoluteUri(false);
			$this->relocate($new_uri, null, true);
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

	// static convenience methods
	// {{{ public static function initVar()

	/**
	 * Initializes a variable
	 *
	 * Static convenience method to initialize a local variable with a value 
	 * from one of the PHP global arrays.
	 *
	 * @param string $name the name of the variable to lookup.
	 * @param integer $types a bitwise combination of self::VAR_*
	 *                        constants. Defaults to
	 *                        {@link SiteApplication::VAR_POST} |
	 *                        {@link SiteApplication::VAR_GET}.
	 * @param mixed $default the value to return if variable is not found in
	 *                        the super-global arrays.
	 *
	 * @return mixed the value of the variable.
	 */
	public static function initVar($name, $default = null, $types = 0)
	{
		$var = $default;

		if ($types == 0)
			$types = self::VAR_POST | self::VAR_GET;

		if (($types & self::VAR_POST) != 0
			&& isset($_POST[$name]))
				$var = $_POST[$name];

		elseif (($types & self::VAR_GET) != 0
			&& isset($_GET[$name]))
				$var = $_GET[$name];

		elseif (($types & self::VAR_REQUEST) != 0
			&& isset($_REQUEST[$name]))
				$var = $_REQUEST[$name];

		elseif (($types & self::VAR_COOKIE) != 0
			&& isset($_COOKIE[$name]))
				$var = $_COOKIE[$name];

		elseif (($types & self::VAR_SERVER) != 0
			&& isset($_SERVER[$name]))
				$var = $_SERVER[$name];

		elseif (($types & self::VAR_SESSION) != 0
			&& isset($_SESSION[$name]))
				$var = $_SESSION[$name];

		elseif (($types & self::VAR_FILES) != 0
			&& isset($_FILES[$name]))
				$var = $_FILES[$name];

		elseif (($types & self::VAR_ENV != 0)
			&& isset($_ENV[$name]))
				$var = $_ENV[$name];

		return $var;
	}

	// }}}
}

?>
