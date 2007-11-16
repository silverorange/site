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

	/**
	 * Configuration setting definitions of this config module
	 *
	 * @var array
	 *
	 * @see SiteConfigModule::addDefinition()
	 * @see SiteConfigModule::addDefinitions()
	 */
	private $definitions = array();

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

			if (!array_key_exists($section_name, $this->definitions)) {
				throw new SiteException(sprintf(
					"Cannot load configuration because a section with the ".
					"name '%s' is not defined.", $section_name));
			}

			foreach ($section_values as $name => $value) {
				if (!array_key_exists($name,
					$this->definitions[$section_name])) {
					throw new SiteException(sprintf(
						"Cannot load configuration because the section '%s' ".
						"does not define a setting with a name of '%s'.",
						$section_name, $name));
				}
			}

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
	// {{{ public function addDefinitions()

	/**
	 * Adds multiple configuration setting definitions to this config module
	 *
	 * Only defined settings may be loaded from an ini file. Other settings
	 * will throw exceptions when they are loaded.
	 *
	 * @param array $definitions an associative array of setting definitions.
	 *                            The array is of the form:
	 *                            qualified_name => default_value where
	 *                            qualified_name is a string containing the
	 *                            section name and the setting name separated
	 *                            by a '.'. For example, a setting for 'dsn' in
	 *                            the 'database' section would have a qualified
	 *                            name of 'database.dsn'. The default value is
	 *                            a string containing the value to use for the
	 *                            setting if the setting does not exist in the
	 *                            loaded configuration file. Use null to
	 *                            specify no default.
	 *
	 * @throws SiteException if the qualified name does not contain a section
	 *                       and a setting name.
	 */
	public function addDefinitions(array $definitions)
	{
		foreach ($definitions as $qualified_name => $default_value) {
			if (strpos($qualified_name, '.') === false) {
				throw new SiteException(sprintf(
					"Qualified name of configuration definition '%s' must be ".
					"of the form section.name.", $qualified_name));
			}

			list($section, $name) = explode('.', $qualified_name, 2);
			$this->addDefinition($section, $name, $default_value);
		}
	}

	// }}}
	// {{{ public function addDefinition()

	/**
	 * Adds a configuration setting definition to this config module
	 *
	 * Only defined settings may be loaded from an ini file. Other settings
	 * will throw exceptions when they are loaded.
	 *
	 * @param string $section the section the setting belongs to.
	 * @param string $name the name of the setting.
	 * @param string $default_value optional. The default value of the setting.
	 *                               Used if the setting does exist in the
	 *                               loaded ini file.
	 *
	 * @throws SiteException if the section is not a valid section name. Valid
	 *                       section names follow the rules for PHP identifiers.
	 * @throws SiteException if the name is not a valid name. Valid names follow
	 *                       the rules for PHP identifiers.
	 */
	public function addDefinition($section, $name, $default_value = null)
	{
		$section = (string)$section;
		$name = (string)$name;

		if (!$this->isValidKey($section)) {
			throw new SiteException(sprintf(
				"Section '%s' in configuration definition is not a valid name.",
				$section));
		}

		if (!$this->isValidKey($name)) {
			throw new SiteException(sprintf(
				"Name '%s' in configuration definition is not a valid name.",
				$name));
		}

		if (!array_key_exists($section, $this->definitions))
			$this->definitions[$section] = array();

		$this->definitions[$section][$name] = $default_value;
	}

	// }}}
	// {{{ private function isValidKey()

	/**
	 * Checks whether or not a name is a valid section name or setting name
	 *
	 * @param string $name the name to check.
	 *
	 * @return boolean true if the name is valid and false if it is not.
	 */
	private function isValidKey($name)
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
