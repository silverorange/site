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
 * @copyright 2004-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteApplication extends SiteObject
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
	 * Modules of this application
	 *
	 * Application modules are pieces of code that add specific functionality
	 * to an application such as database connectivity, session handling or
	 * configuration.
	 *
	 * This is an associative array of the modules of this application. The
	 * array is of the form 'module identifier' => 'module'.
	 *
	 * @var array
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 * @see SiteApplication::addModule()
	 */
	protected $modules = array();

	/**
	 * Modules of this application indexed by provided features
	 *
	 * This array may contain a single module multiple times if the module
	 * provides multiple features.
	 *
	 * @var array
	 *
	 * @see SiteApplicationModule::provides()
	 */
	protected $modules_by_provides = array();

	/**
	 * The current locale of this application
	 *
	 * This must be a two-letter ISO 639 langauge code followed by an
	 * underscore then followed by a two-letter ISO 3166 country code.
	 * Additional locale specifiers such as character encoding and currency
	 * may optionally follow, separated by a period character. Example of valid
	 * locale strings are:
	 *
	 *  - en_CA
	 *  - fr_FR
	 *  - en_CA.utf8
	 *  - fr_FR.euro
	 *
	 * The following locale strings are used on some systems and are not valid:
	 *
	 *  - Brazillian
	 *  - en
	 *  - C
	 *  - POSIX
	 *
	 * @var string
	 */
	protected $locale;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new application
	 *
	 * When this application is created, the default modules are loaded. See
	 * {@link SiteApplication::getDefaultModuleList()}.
	 *
	 * If a configuration filename is specified when this application is
	 * created, a configuration module is created and loaded before the other
	 * application modules are initialized. SiteApplication provides a hook to
	 * configure modules before initModules() is called in
	 * the {@link SiteApplication::config()} method.
	 *
	 * @param string $id a unique identifier for this application.
	 * @param string $config_filename optional. The filename of the
	 *                                 configuration file. If not specified,
	 *                                 no special configuration is performed.
	 */
	public function __construct($id, $config_filename = null)
	{
		$this->id = $id;

		$this->addDefaultModules();

		$this->default_time_zone = new Date_TimeZone('UTC');

		if ($config_filename !== null) {

			try {
				$config_module = $this->getModule('SiteConfigModule');
			} catch (SiteException $e) {
				require_once 'Site/SiteConfigModule.php';
				$config_module = new SiteConfigModule($this);
				$this->addModule($config_module, 'config');
			}

			$this->addConfigDefinitions($config_module);
			$config_module->load($config_filename);
			$this->configure($config_module);
		}
	}

	// }}}
	// {{{ abstract public function run()

	/**
	 * Run the application.
	 */
	abstract public function run();

	// }}}
	// {{{ public function getLocale()

	/**
	 * Gets the current locale of this application
	 *
	 * @return string the locale of this application. This will be a two-letter
	 *                 ISO 639 langauge code followed by an underscore followed
	 *                 by a two-letter ISO 3166 country code. Additional locale
	 *                 specifiers such as character encoding and currency may
	 *                 optionally follow, separated by a period character.
	 */
	public function getLocale()
	{
		return $this->locale;
	}

	// }}}
	// {{{ public function getCountry()

	/**
	 * Gets the country of this application
	 *
	 * @param string $locale optional. If specified, the coutnry code for the
	 *                        specified locale is returned. Otherwise, the
	 *                        current locale of this application is used.
	 *
	 * @return string the two-letter ISO 3166 country code of this application.
	 */
	public function getCountry($locale = null)
	{
		$country = null;

		if ($locale === null)
			$locale = $this->locale;

		if ($locale !== null)
			$country = substr($locale, 3, 2);

		return $country;
	}

	// }}}

	// module methods
	// {{{ public function addModule()

	/**
	 * Adds a module to this application
	 *
	 * @param SiteApplicationModule $module the module to add to this
	 *                                       application.
	 * @param string $id an identifier for this module.
	 *
	 * @throws SiteException if a module with the given identifier already
	 *                       exists in this application.
	 * @throws SiteException if the module identifier collides with a property
	 *                       of this application.
	 * @throws SiteException if the module depends on a feature that no module
	 *                       in this application provides.
	 * @throws SiteException if the module provides a feature already provided
	 *                       by an existing module in this application.
	 */
	public function addModule(SiteApplicationModule $module, $id)
	{
		// check identifier against other modules
		if (isset($this->modules[$id]))
			throw new SiteException(sprintf(
				"A module with the identifier '%s' already exists in this ".
				"applicaiton.", $id));

		// check identifier against properties
		$properties = get_object_vars($this);
		if (array_key_exists($id, $properties))
			throw new SiteException(sprintf(
				"Invalid module identifier '%s'. Module identifiers must ".
				"not be the same as any of the property names of this ".
				"application object.", $id));

		// check module dependencies
		foreach ($module->depends() as $depend) {
			if (!isset($this->modules_by_provides[$depend])) {
				throw new SiteException(sprintf(
					"Module %s depends on feature '%s' which is not provided ".
					"by any module in this application.",
					get_class($module), $depend));
			}
		}

		// check module against provides list
		foreach ($module->provides() as $provide) {
			if (isset($this->modules_by_provides[$provide])) {
				throw new SiteException(sprintf(
					"Module feature '%s' already provided by %s.",
					$provide,
					get_class($this->modules_by_provides[$provide])));
			}
		}

		// add module provides
		foreach ($module->provides() as $provide)
			$this->modules_by_provides[$provide] = $module;

		// add module
		$this->modules[$id] = $module;
	}

	// }}}
	// {{{ public function getModule()

	/**
	 * Gets a module of this application by a provided feature
	 *
	 * This method is useful for getting modules of this application without
	 * needing to know the module identifier. For example, you can get a
	 * session module of this application using:
	 * <code>
	 * <?php
	 * $session = $app->getModule('SiteSessionModule');
	 * ?>
	 * </code>
	 * This use is encouraged for module developers. For site-level code, use
	 * the magic get and set methods provided by this application to access
	 * modules by module identifiers.
	 *
	 * @param string $feature the provided feature.
	 *
	 * @return SiteApplicationModule the module of this application that
	 *                                provides the given feature.
	 *
	 * @throws SiteException if no module in this application provides the
	 *                       given feature.
	 */
	public function getModule($feature)
	{
		if (!isset($this->modules_by_provides[$feature]))
			throw new SiteException(sprintf(
				"Application does not have a module that provides '%s'",
				$feature));

		return $this->modules_by_provides[$feature];
	}

	// }}}
	// {{{ public function hasModule()

	/**
	 * Checks if this application has a module that provides the specified
	 * feature
	 *
	 * This method is useful for checking if an application provides certain
	 * featured. For example, you can see if an application supports session
	 * features using:
	 * <code>
	 * <?php
	 * if ($app->hasModule('SiteSessionModule')) {  ... session code ... }
	 * ?>
	 * </code>
	 *
	 * @param string $feature the provided feature.
	 *
	 * @return boolean true if this applicaiton has a module that provides the
	 *                  specified feature and false if it does not.
	 */
	public function hasModule($feature)
	{
		return array_key_exists($feature, $this->modules_by_provides);
	}

	// }}}
	// {{{ protected function initModules()

	/**
	 * Initializes all modules in this application
	 */
	protected function initModules()
	{
		foreach ($this->modules as $module)
			$module->init();
	}

	// }}}
	// {{{ protected function addDefaultModules()

	/**
	 * Adds the default modules used by this application to this application
	 *
	 * Modules listed in the default module list are automatically added in
	 * their dependent order.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 *
	 * @throws SiteException if a circular module dependency is detected.
	 * @throws SiteException if a module with the given identifier already
	 *                       exists in this application.
	 * @throws SiteException if the module identifier collides with a property
	 *                       of this application.
	 * @throws SiteException if the module depends on a feature that no module
	 *                       in this application provides.
	 * @throws SiteException if the module provides a feature already provided
	 *                       by an existing module in this application.
	 */
	protected function addDefaultModules()
	{
		$modules = array();
		$module_ids = array();
		$modules_by_provides = array();
		$dependent_stack = array();
		$added_modules = array();

		// instantiate default modules
		foreach ($this->getDefaultModuleList() as $module_id => $module_class) {
			$module = new $module_class($this);
			$modules[] = $module;
			$module_ids[spl_object_hash($module)] = $module_id;
			foreach ($module->provides() as $provide)
				$modules_by_provides[$provide] = $module;
		}

		// add existing modules to array so dependency resolution can use
		// existing modules of this application
		foreach ($this->modules as $module)
			$added_modules[spl_object_hash($module)] = true;

		// add existing provides to array so dependency resolution can use
		// existing modules of this application
		$modules_by_provides += $this->modules_by_provides;

		// add default modules to this application
		foreach ($modules as $module) {
			if (!array_key_exists(spl_object_hash($module), $added_modules)) {
				$this->addDefaultModule($module_ids, $modules_by_provides,
					$added_modules, $module, $dependent_stack);
			}
		}
	}

	// }}}
	// {{{ protected function addDefaultModule()

	/**
	 * Adds a default module to this application
	 *
	 * Default module dependencies of the module are added recursively.
	 *
	 * @param array $module_ids a reference to the array of module identifiers.
	 *                           The array is indexed by the module object
	 *                           hash and the module identifier is the value.
	 * @param array $modules_by_provides a refrerence to the array of modules
	 *                                    available indexed by features the
	 *                                    modules provide.
	 * @param array $added_modules a reference to the array of modules already
	 *                              added to this application. The array is
	 *                              keyed with module object hashes and has
	 *                              true values.
	 * @param SiteApplicationModule $module the module to add to this
	 *                                       application.
	 * @param array $depdendency_stack a reference to the stack of modules
	 *                                  depending on the specified module to
	 *                                  be added. This is used to detect
	 *                                  circular dependencies.
	 *
	 * @throws SiteException if a circular module dependency is detected.
	 * @throws SiteException if a module with the given identifier already
	 *                       exists in this application.
	 * @throws SiteException if the module identifier collides with a property
	 *                       of this application.
	 * @throws SiteException if the module depends on a feature that no module
	 *                       in this application provides.
	 * @throws SiteException if the module provides a feature already provided
	 *                       by an existing module in this application.
	 */
	protected function addDefaultModule(array &$module_ids,
		array &$modules_by_provides, array &$added_modules,
		SiteApplicationModule $module, array &$dependent_stack)
	{
		// check for circular dependency
		if (array_key_exists(spl_object_hash($module), $dependent_stack)) {
			$circular_dependency = '';
			foreach ($dependent_stack as $dependency)
				$circular_dependency.= get_class($dependency). ' => ';

			$circular_dependency.= get_class($module);
			throw new SiteException(sprintf(
				"Circular module dependency detected:\n%s",
				$circular_dependency));
		}

		// module object is value only so we can get nice error messages
		$dependent_stack[spl_object_hash($module)] = $module;

		// add module dependencies 
		foreach ($module->depends() as $depend) {

			if (!isset($modules_by_provides[$depend]))
				throw new SiteException(sprintf(
					"Module %s depends on '%s' but no module provides this ".
					"feature.",
					get_class($module), $depend));

			$depend_module = $modules_by_provides[$depend];

			if (!array_key_exists(spl_object_hash($depend_module),
				$added_modules)) {
				$this->addDefaultModule($module_ids, $modules_by_provides,
					$added_modules, $depend_module, $dependent_stack);
			}

		}

		// all dependencies loaded, pop dependent stack
		array_pop($dependent_stack);

		// add module
		$added_modules[spl_object_hash($module)] = true;
		$this->addModule($module, $module_ids[spl_object_hash($module)]);
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this application
	 *
	 * The list of modules is an associative array of the form
	 * 'module identifier' => 'module class name'. After instantiation, loaded
	 * modules are accessible as public, read-only properties of this
	 * application. The public property names correspond directly to the module
	 * identifiers specified in the module list array.
	 *
	 * No modules are loaded by default. Subclasses of SiteApplication may
	 * specify their own list of modules to load by overriding this method.
	 *
	 * @return array the default list of modules to load for this application.
	 */
	protected function getDefaultModuleList()
	{
		return array();
	}

	// }}}
	// {{{ protected function configure()

	/**
	 * Configures modules of this application
	 *
	 * This method is run after the configuration has been loaded. Developers
	 * should add module-specific configuration here.
	 *
	 * @param SiteConfigModule $config the config module of this application
	 *                                  to use for configuring the other
	 *                                  modules.
	 */
	protected function configure(SiteConfigModule $config)
	{
		$config->configure();
	}

	// }}}
	// {{{ protected function addConfigDefinitions()

	/**
	 * Adds configuration definitions to the config module of this application
	 *
	 * This method runs before the configuration is loaded. Developers should
	 * add configuration definitions for every setting that will be loaded
	 * by the config module.
	 *
	 * Packages may provide a convenient list of configuration definitions
	 * in the static package class.
	 *
	 * @param SiteConfigModule $config the config module of this application to
	 *                                  witch to add the config definitions.
	 */
	protected function addConfigDefinitions(SiteConfigModule $config)
	{
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

		if (($types & self::VAR_POST) != 0 &&
			array_key_exists($name, $_POST)) {
				$var = $_POST[$name];

		} elseif (($types & self::VAR_GET) != 0 &&
			array_key_exists($name, $_GET)) {
				$var = $_GET[$name];

		} elseif (($types & self::VAR_REQUEST) != 0 &&
			array_key_exists($name, $_REQUEST)) {
				$var = $_REQUEST[$name];

		} elseif (($types & self::VAR_COOKIE) != 0 &&
			array_key_exists($name, $_COOKIE)) {
				$var = $_COOKIE[$name];

		} elseif (($types & self::VAR_SERVER) != 0 &&
			array_key_exists($name, $_SERVER)) {
				$var = $_SERVER[$name];

		} elseif (($types & self::VAR_SESSION) != 0	&&
			array_key_exists($name, $_SESSION)) {
				$var = $_SESSION[$name];

		} elseif (($types & self::VAR_FILES) != 0 &&
			array_key_exists($name, $_FILES)) {
				$var = $_FILES[$name];

		} elseif (($types & self::VAR_ENV) != 0) {
			// Use getenv() instead of $_ENV so we can load environment
			// variables that were set after the script started running.
			$value = getenv($name);
			if ($value !== false)
				$var = $value;
		}

		return $var;
	}

	// }}}
}

?>
