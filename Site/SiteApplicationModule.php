<?php

/**
 * Base class for an application module
 *
 * Modules provide specific features to an application. For example, a session
 * module provides the ability to use sessions. Features are expressed as
 * strings and are usually the name of the class of an application module. For
 * example, a SiteSessionModule module would provide the 'SiteSessionModule'
 * feature. Other modules that depend on a session module can ask the
 * application if any modules provide the 'SiteSessionModule' feature.
 *
 * @package   Site
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteApplicationModule extends SiteObject
{


	/**
	 * Reference to the application object that contains this module
	 *
	 * @var SiteApplication
	 */
	protected $app;




	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
	}




	/**
	 * A cache of the provides list for each module used in the application
	 *
	 * The array key is the module class and the value is an array of provided
	 * dependencies.
	 */
	private static $provides_by_module = [];




	/**
	 * Gets the module features this module depends on
	 *
	 * By default, modules do not depend on other module features.
	 *
	 * @return array an array of {@link SiteApplicationModuleDependency}
	 *                        objects defining the features this module
	 *                        depends on.
	 */
	public function depends()
	{
		return [];
	}




	/**
	 * Gets the features this module provides
	 *
	 * By default, this is the class inheritance path up to and excluding
	 * SiteApplicationModule. This is normally all that is required but
	 * subclasses may specify additional features.
	 *
	 * @return array an array of features this module provides.
	 */
	public function provides()
	{
		$module = static::class;

		if (!array_key_exists($module, self::$provides_by_module)) {
			self::$provides_by_module[$module] = [];
			$reflector = new ReflectionObject($this);
			while ($reflector->getName() != self::class) {
				self::$provides_by_module[$module][] = $reflector->getName();
				$reflector = $reflector->getParentClass();
			}
		}

		return self::$provides_by_module[$module];
	}




	/**
	 *
	 */
	abstract public function init();


}

?>
