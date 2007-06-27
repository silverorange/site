<?php

require_once 'Zend/Config.php';
require_once 'Zend/Config/Ini.php';
require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for site configuration
 *
 * @package   Site
 * @copyright 2006 silverorange
 */
class SiteConfigModule extends SiteApplicationModule
{
	// {{{ private properties

	private $zend_config = null;
	private $filename;
	private $section = 'default';

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this module
	 */
	public function init()
	{
		if ($this->zend_config === null)
			$this->load();
	}

	// }}}
	// {{{ public function load()

	/**
	 * Loads configuration
	 */
	public function load()
	{
		$this->zend_config = new Zend_Config(
			Zend_Config_Ini::load($this->filename, $this->section));
	}

	// }}}
	// {{{ public function setFilename()

	/**
	 * Sets the configuration filename
	 *
	 * @param string $name the path and name of the confuration file.
	 */
	public function setFilename($filename)
	{
		$this->filename = $filename;
	}

	// }}}
	// {{{ public function setScope()

	/**
	 * Sets the configuration section
	 *
	 * @param string $section the section to load from the config file.
	 */
	public function setScope($section)
	{
		$this->section = $section;
	}

	// }}}
	// {{{ private function __get()

	/**
	 * Gets a config value
	 *
	 * @param string $name the name of the config value to get.
	 *
	 * @return mixed the value of the config setting.
	 */
	private function __get($name)
	{
		return $this->zend_config->$name;
	}

	// }}}
	// {{{ private function __isset()

	/**
	 * Checks for existence of a config value
	 *
	 * @param string $name the name of the config value to check.
	 *
	 * @return boolean whether the config setting exists.
	 */
	private function __isset($name)
	{
		return isset($this->zend_config->$name);
	}

	// }}}
}

?>
