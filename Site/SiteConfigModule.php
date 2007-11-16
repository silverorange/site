<?php

require_once 'Swat/exceptions/SwatException.php';
require_once 'Site/SiteApplicationModule.php';
require_once 'Site/SiteConfigSection.php';

/**
 * Web application module for site configuration
 *
 * The site configuration module loads an ini file of application settings.
 * Settings in the ini file are grouped into sections. Properties within
 * sections are treated as sub-objects of the config module. For example, the
 * following section:
 *
 * <code>
 * [database]
 * dsn = test@test.com
 * user = test
 * </code>
 *
 * Will parse into to following config structure:
 * <code>
 * <?php
 * $config->database->dsn = 'test@test.com';
 * $config->database->user = 'test';
 * ?>
 * </code>
 *
 * Because of the way the site configuration module parses ini properties into
 * PHP properties, all ini settings names must be valid PHP variable names.
 *
 * If a non-existant configuration property is accessed, an exception will be
 * thrown.
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteConfigModule extends SiteApplicationModule
{
	// {{{ private properties

	/**
	 * The filename of the ini file to use for this configuration module
	 *
	 * @var string
	 *
	 * @see SiteConfigurationModule::setFilename()
	 */
	private $filename;

	/**
	 * The sections of this configuration
	 *
	 * This array is indexed by section names and contains SiteConfigSection
	 * objects as parsed from the ini file.
	 *
	 * @var array
	 */
	private $sections = array();

	/**
	 * Whether or not this config module has been loaded
	 *
	 * @var boolean
	 */
	private $loaded = false;

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this module
	 *
	 * If this configuration module is not already loaded it is loaded here.
	 */
	public function init()
	{
		if (!$this->loaded)
			$this->load();
	}

	// }}}
	// {{{ public function load()

	/**
	 * Loads configuration from ini file
	 *
	 * @throws SwatException if any of the ini file key names are invalid.
	 */
	public function load()
	{
		$ini_array = parse_ini_file($this->filename, true);
		foreach ($ini_array as $section_name => $section_values) {
			$section = new SiteConfigSection($section_name, $section_values);
			$this->sections[$section_name] = $section;
		}
		$this->loaded = true;
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
	// {{{ protected function isValidKey()

	/**
	 * Checks whether or not a key name is valid
	 *
	 * @param string $name the key name to check.
	 *
	 * @return boolean true if the key name is valid and false if it is not.
	 */
	protected function isValidKey($name)
	{
		$regexp = '/^[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*$/';
		return (preg_match($regexp, $name) == 1);
	}

	// }}}
	// {{{ private function __get()

	/**
	 * Gets a section of this config module
	 *
	 * @param string $name the name of the config section to get.
	 *
	 * @return SiteConfigSection the config section.
	 */
	private function __get($name)
	{
		if (!array_key_exists($name, $this->sections))
			throw new SiteException(sprintf(
				"Section '%s' does no exist in this config module.",
				$name));

		return $this->sections[$name];
	}

	// }}}
	// {{{ private function __isset()

	/**
	 * Checks for existence of a section within this config module
	 *
	 * @param string $name the name of the section value to check.
	 *
	 * @return boolean true if the section exists in this config module and
	 *                  false if it does not.
	 */
	private function __isset($name)
	{
		return array_key_exists($name, $this->sections);
	}

	// }}}
}

?>
