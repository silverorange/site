<?php

require_once 'Swat/exceptions/SwatException.php';
require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for site configuration
 *
 * The site configuration module loads an ini file of application settings.
 * By default, only settings from the "default" section are loaded. This may
 * be changed using the {@link SiteConfigModule::setScope()} method.
 *
 * Settings in the ini file may use a "dot notation". Properties with dot
 * notation are treated as sub-objects of the config module. For example, the
 * following section:
 *
 * <code>
 * [default]
 * database.dsn = test@test.com
 * database.user = test
 * time_zone = 'America/Halifax'
 * </code>
 *
 * Will parse into to following config structure:
 * <code>
 * <?php
 * $config->database->dsn = 'test@test.com';
 * $config->database->user = 'test';
 * $config->time_zone = 'America/Halifax';
 * ?>
 *
 * Because of the way the site configuration module parses ini properties into
 * PHP properties, all property names must be valid PHP variable names.
 *
 * If a non-existant configuration property is accessed, its value will be
 * null.
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
	 * The ini section from which to load site configuration
	 *
	 * Defaults to "default".
	 *
	 * @var string
	 *
	 * @see SiteConfigurationModule::setScope()
	 */
	private $section = 'default';

	/**
	 * The parsed configuration of this configuration module
	 *
	 * @var stdObject
	 */
	private $config;

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this module
	 *
	 * If this configuration module is not already loaded it is loaded here.
	 */
	public function init()
	{
		if ($this->config === null)
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
		$ini_array = $ini_array[$this->section];
		$this->config = $this->parseIniArray($ini_array);
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
	// {{{ protected function parseIniArray()

	/**
	 * Parses an array of ini file key-value pairs into a complex object
	 * based on dot notation of ini array keys
	 *
	 * Each dot in the ini file key indicates a sub-object in the complex
	 * object. For example, 'email.server.address' => 'test.com' parses to
	 * $config->email->server->address = 'test.com'.
	 *
	 * @param array $ini_array the ini file array to parse.
	 *
	 * @return stdClass the parsed ini array object.
	 *
	 * @throws SwatException if any of the ini array key names are invalid.
	 */
	protected function parseIniArray(array $ini_array)
	{
		$config = new stdClass();

		foreach ($ini_array as $key => $value) {
			$object = $config;

			$key_list = explode('.', $key);
			while (count($key_list) > 1) {
				// get key
				$key = array_shift($key_list);

				if (!$this->isValidKey($key))
					throw new SwatException(sprintf(
						"Invalid configuration key '%s' in file '%s'.",
						$key, $this->filename));

				// create key if it doesn't exist
				if (!isset($object->$key))
					$object->$key = new stdClass();

				// set up next object 
				$object = $object->$key;
			}

			if (count($key_list) > 0)
				$key = array_shift($key_list);

			if (!$this->isValidKey($key))
				throw new SwatException(sprintf(
					"Invalid configuration key '%s' in file '%s'.",
					$key, $this->filename));

			// assign value to current key
			$object->$key = $value;
		}

		return $config;
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
	 * Gets a config value
	 *
	 * @param string $name the name of the config value to get.
	 *
	 * @return string the value of the config setting or null if the config
	 *                 setting is not set.
	 */
	private function __get($name)
	{
		$value = null;
		if (isset($this->config->$name))
			$value = $this->config->$name;

		return $value;
	}

	// }}}
	// {{{ private function __isset()

	/**
	 * Checks for existence of a configuration value
	 *
	 * @param string $name the name of the configuration value to check.
	 *
	 * @return boolean true if the configuration value exists and false if it
	 *                  does not.
	 */
	private function __isset($name)
	{
		return isset($this->config->$name);
	}

	// }}}
}

?>
