<?php

require_once 'Site/SiteCommandLineApplication.php';
require_once 'PackageConfig.php';

/**
 * Command line application for checking if an ini file is valid
 *
 * Because of the way the site configuration module parses ini properties into
 * PHP properties, all ini settings names must be valid PHP variable names.
 * Additionally, ini properties must be defined to be valid.
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteConfigChecker extends SiteCommandLineApplication
{
	// {{{ private properties

	/**
	 * Configuration setting definitions of this config checker
	 *
	 * @var array
	 *
	 * @see SiteConfigChecker::addDefinition()
	 * @see SiteConfigChecker::addDefinitions()
	 */
	private $definitions = array();

	/**
	 * @var string
	 */
	private $filename = '';

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $title, $documentation)
	{
		parent::__construct($id, $title, $documentation);

		$quiet = new SiteCommandLineArgument(array('-q', '--quiet'),
			'setQuiet', 'Turns on quiet mode. The return value of the process '.
			'indicates success or failure but no output is displayed.');

		$this->addCommandLineArgument($quiet);
	}

	// }}}
	// {{{ public function run()

	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();

		if (!$this->check()) {
			$this->terminate("\nConfig file is NOT valid.\n");
		}
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
	// {{{ public function setQuiet()

	/**
	 * Sets whether or not this application is quiet
	 *
	 * @param boolean $quiet optional. Whether or not this application is
	 *                        quiet. If not specified, defaults to true.
	 */
	public function setQuiet($quiet = true)
	{
		$verbosity = ($quiet) ?
			SiteCommandLineApplication::VERBOSITY_NONE :
			SiteCommandLineApplication::VERBOSITY_ALL;

		$this->setVerbosity($verbosity);
	}

	// }}}
	// {{{ public function setFilename()

	public function setFilename($filename)
	{
		$this->filename = (string)$filename;

		$class_name = ucfirst(preg_replace_callback('/-(.)/',
			create_function('$matches', 'return strtoupper($matches[1]);'),
			basename(str_replace('.ini', '', $filename))));
	}

	// }}}
	// {{{ private function check()

	/**
	 * Checks an in file to ensure all the fields are defined
	 *
	 * @return boolean returns true if the file was parsed successfully and
	 *                  false if not.
	 */
	private function check()
	{
		$passed = true;

		$ini_array = parse_ini_file($this->filename, true);
		foreach ($ini_array as $section_name => $section_values) {

			if (!array_key_exists($section_name, $this->definitions)) {
				$this->debug(sprintf(Site::_(
					"Error: [%s] is not defined.\n"),
					$section_name));

				$passed = false;
			}

			foreach ($section_values as $name => $value) {
				if (!array_key_exists($section_name, $this->definitions) ||
					!array_key_exists($name,
						$this->definitions[$section_name])) {

					$this->debug(sprintf(Site::_(
						"Error: in [%s], '%s' is not defined.\n"),
						$section_name, $name));

					$passed = false;
				}
			}
		}

		return $passed;
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
}

?>
