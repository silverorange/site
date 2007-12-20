<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for multiple instances
 *
 * @package   Site
 * @copyright 2007 silverorange
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
		$config = $this->app->getModule('SiteConfigModule');

		$instance_shortname = SiteApplication::initVar(
			'instance', $config->instance->default,
			SiteApplication::VAR_GET | SiteApplication::VAR_ENV);

		$class_name = SwatDBClassMap::get('SiteInstance');
		$this->instance = new $class_name();
		$this->instance->setDatabase($this->app->database->getConnection());
		if (!$this->instance->loadFromShortname($instance_shortname)) {
			throw new SiteException(sprintf("No site instance with the ".
				"shortname '%s' exists.", $instance_shortname));
		}

		$this->overrideConfig($config);
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
	// {{{ protected function overrideConfig()

	protected function overrideConfig(SiteConfigModule $config)
	{
	}

	// }}}
}

?>
