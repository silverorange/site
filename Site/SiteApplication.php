<?php

require_once 'Site/exceptions/SiteException.php';
require_once 'Site/SiteObject.php';
require_once 'Site/SitePage.php';
require_once 'Site/SiteApplicationModule.php';

/**
 * Base class for a web application
 *
 * @package   Site
 * @copyright 2004-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteApplication extends SiteObject
{
	// {{{ class constants

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

	// }}}
	// {{{ protected properties

	/**
	 * The current page of this application
	 *
	 * @var SitePage
	 */
	protected $page = null;

	/**
	 * The base value for all of this application's anchor hrefs
	 *
	 * @var string
	 */
	protected $base_href = null;

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

	// }}}
	// {{{ private properties

	/**
	 * Whether init() has been run on this->page
	 *
	 * @var boolean
	 */
	private $page_initialized = false;

	/**
	 * The execution start time of this application
	 *
	 * @var double 
	 */
	private $start_time = null;

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
		$this->start_time = microtime(true);

		// load default modules
		foreach ($this->getDefaultModuleList() as $module_id => $module_class)
			$this->addModule(new $module_class($this), $module_id);
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
	// {{{ public function init()

	/**
	 * Initializes this application
	 *
	 * Subclasses should implement all application level initialization here
	 * and call whichever SiteApplication::init* methods are necessary.
	 */
	public function init()
	{
		$this->initBaseHref();
		$this->initModules();

		// call this last
		$this->initPage();
	}

	// }}}
	// {{{ public function getPage()

	/**
	 * Gets the current page
	 */
	public function getPage()
	{
		return $this->page;
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

		if ($this->page_initialized)
			$this->page->init();
	}

	// }}}
	// {{{ public function getBaseHref()

	/**
	 * Gets the base value for all application anchor hrefs
	 *
	 * @return string the base value for all application anchor hrefs.
	 */
	public function getBaseHref()
	{
		return $this->base_href;
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
	// {{{ public function getExecutionTime()

	/**
	 * Gets the current execution time of this application in milliseconds
	 *
	 * @return double the current execution time of this application in
	 *                 milliseconds.
	 */
	public function getExecutionTime()
	{
		return microtime(true) - $this->start_time;
	}

	// }}}
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

		$this->modules[$id] = $module;
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
	 */
	public function relocate($uri)
	{
		if (substr($uri, 0, 1) != '/' && strpos($uri, '://') === false)
			$uri = $this->getBaseHref().$uri;

		header('Location: '.$uri);
		exit();
	}

	// }}}
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
	// {{{ protected function initBaseHref()

	/**
	 * Initializes the base href
	 */
	protected function initBaseHref($prefix_length = 0, $secure = false)
	{
		$this->uri = $_SERVER['REQUEST_URI'];

		$uri_array = explode('/', $this->uri);

		$base_uri = implode('/',
			array_slice($uri_array, 0, $prefix_length + 1)).'/';

		$protocol = ($secure) ? 'https://' : 'http://';

		$this->base_href = $protocol.$this->getServerName().$base_uri;
	}

	// }}}
	// {{{ protected function initPage()

	/**
	 * Initializes the page
	 */
	protected function initPage()
	{
		if ($this->page === null)
			$this->page = $this->resolvePage();

		$this->page->init();

		$this->page_initialized = true;
	}

	// }}}
	// {{{ protected function resolvePage()

	/**
	 * Resolves a page for this application
	 *
	 * This method is called if no {@link SitePage} is provided to the
	 * {@link SiteApplication::setPage()} method.
	 */
	protected function resolvePage()
	{
		return new SitePage($this);
	}
	
	// }}}
	// {{{ protected function getServerName()

	/**
	 * Gets the servername
	 *
	 * @return string the servername
	 */
	protected function getServerName()
	{
		return $_SERVER['HTTP_HOST'];
	}

	// }}}
}

?>
