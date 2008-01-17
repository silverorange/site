<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Site/dataobjects/SiteInstance.php';

/**
 * Web application module for multiple instances
 *
 * Configuration settings in a SiteConfigModule can be overriden on a per
 * instance basis by adding rows to the InstanceConfigSetting table.
 *
 * @package   Site
 * @copyright 2007 silverorange
 * @see SiteConfigModule
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

		if ($instance_shortname !== null) {
			$class_name = SwatDBClassMap::get('SiteInstance');
			$this->instance = new $class_name();
			$this->instance->setDatabase($this->app->database->getConnection());
			if (!$this->instance->loadFromShortname($instance_shortname)) {
				throw new SiteException(sprintf("No site instance with the ".
					"shortname '%s' exists.", $instance_shortname));
			}

			$this->overrideConfig($config);
		}
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
		foreach ($this->instance->config_settings as $setting) {
			if ($setting->value !== null) {
				$qualified_name = $setting->name;

				if (strpos($qualified_name, '.') === false) {
					throw new SiteException(sprintf(
						"Name of configuration setting '%s' must be ".
						"fully qualifed and of the form section.name.",
						$qualified_name));
				}

				list($section, $name) = explode('.', $qualified_name, 2);

				$config->$section->$name = $setting->value;
			}
		}
	}

	// }}}
}

?>
