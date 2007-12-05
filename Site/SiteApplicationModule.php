<?php

require_once 'Site/SiteObject.php';
require_once 'Site/SiteApplication.php';

/**
 * Base class for an application module
 *
 * @package   Site
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteApplicationModule extends SiteObject
{
	// {{{ protected properties

	/**
	 * Reference to the application object that contains this module
	 *
	 * @var SiteApplication
	 */
	protected $app;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * By default, modules do not depend on other module features.
	 *
	 * @return array an array of {@link SiteModuleDependency} objects defining
	 *                        the features this module depends on.
	 */
	public function depends()
	{
		return array();
	}

	// }}}
	// {{{ public function provides()

	/**
	 * Gets the features this module provides
	 *
	 * By defualt, this is the class inheritance path up to and excluding
	 * SiteApplicationModule. This is normally all that is required but
	 * subclasses may specify additional features.
	 *
	 * @return array an array of features this module provides.
	 */
	public function provides()
	{
		static $provides = null;

		if ($provides === null) {
			$provides = array();
			$reflector = new ReflectionObject($this);
			while ($reflector->getName() != __CLASS__) {
				$provides[] = $reflector->getName();
				$reflector = $reflector->getParentClass();
			}
		}

		return $provides;
	}

	// }}}
	// {{{ abstract public function init()

	/**
	 *
	 */
	abstract public function init();

	// }}}
}

?>
