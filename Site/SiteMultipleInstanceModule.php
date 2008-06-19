<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Site/dataobjects/SiteInstance.php';
require_once 'Site/exceptions/SiteNotFoundException.php';

/**
 * Web application module for multiple instances
 *
 * This implementation creates a default instance dataobject and assigns it to
 * the internal {@link SiteMultipleInstanceModule::$instance} property.
 * Subsequent calls to <code>$this->app->instance->getInstance()</code> return
 * the {@link SiteInstance} instance object.
 *
 * @package   Site
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteInstance
 */
class SiteMultipleInstanceModule extends SiteApplicationModule
{
	// {{{ protected properties

	/**
	 * The current instance of this site
	 *
	 * @var SiteInstance
	 */
	protected $instance = null;

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this module
	 */
	public function init()
	{
		// The config module is not yet initialized so only file-based setting
		// values are available.
		$config = $this->app->getModule('SiteConfigModule');

		$instance_shortname = SiteApplication::initVar(
			'instance', $config->instance->default,
			SiteApplication::VAR_GET | SiteApplication::VAR_ENV);

		$database = $this->app->getModule('SiteDatabaseModule');
		$db = $database->getConnection();

		if ($instance_shortname !== null) {
			$class_name = SwatDBClassMap::get('SiteInstance');
			$this->instance = new $class_name();
			$this->instance->setDatabase($db);
			if (!$this->instance->loadFromShortname($instance_shortname)) {
				throw new SiteNotFoundException(sprintf(
					"No site instance with the shortname '%s' exists.",
					$instance_shortname));
			}
		}
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The site session module optionally depends on the
	 * SiteDatabaseModule feature.
	 *
	 * @return array an array of {@link SiteApplicationModuleDependency}
	 *                        objects defining the features this module
	 *                        depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();
		$depends[] = new SiteApplicationModuleDependency('SiteDatabaseModule');
		return $depends;
	}

	// }}}
	// {{{ public function getInstance()

	/**
	 * Gets the current instance of this site
	 *
	 * @return SiteInstance the current instance of this site.
	 */
	public function getInstance()
	{
		return $this->instance;
	}

	// }}}
	// {{{ public function getId()

	/**
	 * Helper method to get the id of the current instance
	 *
	 * @return integer the current instance id or null if no instance exists.
	 */
	public function getId()
	{
		$id = null;
		if ($this->instance !== null)
			$id = $this->instance->id;

		return $id;
	}

	// }}}
}

?>
