<?php

require_once 'Date/TimeZone.php';
require_once 'Site/exceptions/SiteException.php';
require_once 'Site/SiteObject.php';
require_once 'Site/SiteApplicationModule.php';
require_once 'Swat/SwatDate.php';

/**
 * Base class for an application
 *
 * @package   Site
 * @copyright 2004-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteApplication extends SiteObject
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
	// {{{ abstract public function run()

	/**
	 * Run the application.
	 */
	abstract public function run();

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
