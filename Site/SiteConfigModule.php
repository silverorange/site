<?php

require_once 'SwatDB/exceptions/SwatDBException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'Site/exceptions/SiteException.php';
require_once 'Site/SiteApplicationModule.php';
require_once 'Site/SiteConfigSection.php';

/**
 * Web application module for site configuration
 *
 * Settings are stored in section-name-value triples. Sections and names are
 * are treated as sub-objects of this config module. Because of the way the
 * site configuration module accesses settings as public properties, all
 * setting sections and names must be valid PHP variable names.
 *
 * All configuration settings must be defined before they may be used by an
 * application. If an undefined configuration value is accessed, an exception
 * will be thrown.
 *
 * Configuration values are accessed through section and name keys on this
 * module. For example, a section with the name 'site' and the names 'title'
 * and 'tagline' is accessed through the config module <code>$config</code> as
 * follows:
 *
 * <code>
 * <?php
 * $config->site->title = 'My Cool Website';
 * $config->site->tagline = 'Supercool';
 * ?>
 * </code>
 *
 * Site configuration values come from three locations with increasing
 * precedence. Higher precedence values override lower values when the value
 * exists in more than one location for the same section and name.
 *
 * <strong>1. INI File:</strong>
 *
 * The site configuration module loads an ini file containing application
 * setting values. Settings in the ini file are grouped into sections. For
 * example:
 *
 * <pre>
 * [site]
 * tile = My Cool Website
 * tagline = Supercool
 * </pre>
 *
 * <strong>2. ConfigSetting Table:</strong>
 *
 * If the application has a database and a table named <em>ConfigSetting</em>
 * exists, settings are loaded from the table. The column <code>name</code>
 * should contain the setting section-name in the form
 * <code>section.name</code> and column <code>value</code> should contain the
 * setting value.
 *
 * Setting values loaded from the database override values loaded from the ini
 * file.
 *
 * Example settings table rows:
 * <pre>
 * +--------------+-----------------+
 * | name         | value           |
 * +--------------+-----------------+
 * | site.title   | My Cool Website |
 * | site.tagline | Supercool       |
 * +--------------+-----------------+
 * </pre>
 *
 * <strong>3. InstanceConfigSetting Table:</strong>
 *
 * If the application uses site instances, settings are loaded from the
 * <em>InstanceConfigSetting</em> table. The format is the same as the
 * <em>ConfigSetting</em> table; however, each setting is also bound to a site
 * instance.
 *
 * Setting values loaded from the instance config setting table in the database
 * override values loaded from both the config setting table and from the ini
 * file.
 *
 * Example instance settings table rows:
 * <pre>
 * +--------------+-----------------+----------+
 * | name         | value           | instance |
 * +--------------+-----------------+----------+
 * | site.title   | My Cool Website | 1        |
 * | site.tagline | Supercool       | 1        |
 * +--------------+-----------------+----------+
 * </pre>
 *
 * <strong>Saving</strong>
 *
 * Configuration setting values may be saved using the
 * {@link SiteConfigModule::save()} method. Settings are only ever saved in the
 * database. File-based saving is intentionally excluded.
 *
 * Setting values are only saved if they are set during runtime and are
 * different than the loaded value.
 *
 * @package   Site
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteConfigModule extends SiteApplicationModule
{
	// {{{ class constants

	/**
	 * Value was not loaded and the default value is used.
	 */
	const SOURCE_DEFAULT  = 1;

	/**
	 * Value was loaded from the ini file.
	 */
	const SOURCE_FILE     = 2;

	/**
	 * Value was loaded from the <code>ConfigSetting</code> database table.
	 */
	const SOURCE_DATABASE = 3;

	/**
	 * Value was loaded from the <code>InstanceConfigSetting</code> database
	 * table.
	 */
	const SOURCE_INSTANCE = 4;

	/**
	 * Value was set during runtime.
	 */
	const SOURCE_RUNTIME  = 5;

	// }}}
	// {{{ private properties

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

	/**
	 * The path to the .ini of this config module.
	 *
	 * @var string
	 */
	private $config_filename;

	/**
	 * Data sources for current config settings
	 *
	 * The array is of the form:
	 * <code>
	 * <?php
	 * array(
	 *    'mysection' => array(
	 *        'mysetting' => 1,
	 *        'mysetting' => 2,
	 *    ),
	 * );
	 * ?>
	 * </code>
	 *
	 * @var array
	 */
	private $setting_sources = array();

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this module
	 *
	 * If this configuration module is not already loaded it is loaded here.
	 * Database and instance database setting values are loaded here.
	 */
	public function init()
	{
		if (!$this->loaded)
			$this->load();

		$this->loadDatabaseValues();
		$this->loadInstanceValues();
	}

	// }}}
	// {{{ public function load()

	/**
	 * Loads configuration from ini file
	 *
	 * Only defined settings are loaded.
	 *
	 * @param string $filename the filename of the ini file to load.
	 */
	public function load($filename)
	{
		$this->config_filename = $filename;
		$this->loadFileValues();

		// create default sections with default values
		foreach ($this->definitions as $section_name => $default_values) {
			if (!array_key_exists($section_name, $this->sections)) {
				$this->sections[$section_name] = new SiteConfigSection(
					$section_name, $default_values, $this,
						self::SOURCE_DEFAULT);
			}
		}

		$this->loaded = true;
	}

	// }}}
	// {{{ public function save()

	/**
	 * Save the information contained in this module to the appropriate
	 * database table
	 *
	 * If the application uses site instances, the setting values are saved in
	 * the <em>InstanceConfigSetting</em> table. Otherwise, they are saved in
	 * the <em>ConfigSetting</em>.
	 *
	 * If there is no database module, the settings values of this config
	 * module can not be saved.
	 *
	 * @param array $settings An array of config settings to save.
	 */
	public function save(array $settings)
	{
		// if there is no database module, do nothing
		if (!$this->app->hasModule('SiteDatabaseModule'))
			return;

		if ($this->app->getInstance() === null) {
			$this->saveDatabaseValues($settings);
		} else {
			$this->saveInstanceValues($settings);
		}
	}

	// }}}
	// {{{ public function addDefinitions()

	/**
	 * Adds multiple configuration setting definitions to this config module
	 *
	 * Only defined settings are be loaded from an ini file. Other settings in
	 * ini files are ignored.
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
	 * Only defined settings are be loaded from an ini file. Other settings in
	 * ini files are ignored.
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
		$this->setting_sources[$section][$name] = self::SOURCE_DEFAULT;
	}

	// }}}
	// {{{ public function postInitConfigure()

	/**
	 * Configures the application
	 *
	 * This method is run after modules have all been initialized. All
	 * overridden config settings from the database are loaded when this method
	 * runs. Developers may add application configuration here. Alternatively,
	 * configuration may be added to the
	 * {@link SiteApplication::postInitConfigure()} method().
	 */
	public function postInitConfigure()
	{
	}

	// }}}
	// {{{ public function configure()

	/**
	 * Configures other modules of the application
	 *
	 * This method is run by the application immediately after the
	 * configuration has been loaded from the config file. Developers may add
	 * module-specific configuration here. Alternatively, module-specific
	 * configuration may be added to the {@link SiteApplication::configure()}
	 * method.
	 */
	public function configure()
	{
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The config module optionally depends on the SiteDatabaseModule and
	 * SiteInstanceModule features.
	 *
	 * @return array an array of {@link SiteApplicationModuleDependency}
	 *                        objects defining the features this module
	 *                        depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();

		$depends[] = new SiteApplicationModuleDependency(
			'SiteDatabaseModule', false);

		$depends[] = new SiteApplicationModuleDependency(
			'SiteMultipleInstanceModule', false);

		return $depends;
	}

	// }}}
	// {{{ public function __toString()

	/**
	 * Gets a string representation of this config module
	 *
	 * This gets the loaded config values split into the loaded sections.
	 *
	 * @return string a string representation of this config module.
	 */
	public function __toString()
	{
		ob_start();
		foreach ($this->sections as $section) {
			echo $section, "\n";
		}
		return ob_get_clean();
	}

	// }}}
	// {{{ public function getSource()

	/**
	 * Gets the source of a config setting value
	 *
	 * @param string $section the config setting section.
	 * @param string $section the config setting name.
	 *
	 * @return integer either {@link SiteConfigModule::SOURCE_DEFAULT},
	 *                 {@link SiteConfigModule::SOURCE_FILE},
	 *                 {@link SiteConfigModule::SOURCE_DATABASE},
	 *                 {@link SiteConfigModule::SOURCE_INSTANCE} or
	 *                 {@link SiteConfigModule::SOURCE_RUNTIME}.
	 *
	 * @throws SiteException if there is no config setting defined for the
	 *                       given section and name.
	 */
	public function getSource($section, $name)
	{
		if (!array_key_exists($section, $this->setting_sources)) {
			throw new SiteException(sprintf(
				"Section '%s' does not exist in this config module.",
				$name));
		}

		$setting_names = $this->setting_sources[$section];

		if (!array_key_exists($name, $setting_names)) {
			throw new SiteException(sprintf(
				"Setting '%s' does not exist in the section '%s'.",
				$name, $section));
		}

		return $setting_names[$name];
	}

	// }}}
	// {{{ public function setSource()

	/**
	 * Sets the source of a config setting value
	 *
	 * @param string $section the config setting section.
	 * @param string $section the config setting name.
	 * @param integer $source either {@link SiteConfigModule::SOURCE_DEFAULT},
	 *                         {@link SiteConfigModule::SOURCE_FILE},
	 *                         {@link SiteConfigModule::SOURCE_DATABASE},
	 *                         {@link SiteConfigModule::SOURCE_INSTANCE} or
	 *                         {@link SiteConfigModule::SOURCE_RUNTIME}.
	 *
	 * @throws SiteException if there is no config setting defined for the
	 *                       given section and name.
	 */
	public function setSource($section, $name, $source)
	{
		if (!array_key_exists($section, $this->setting_sources)) {
			throw new SiteException(sprintf(
				"Section '%s' does not exist in this config module.",
				$name));
		}

		$setting_names = $this->setting_sources[$section];

		if (!array_key_exists($name, $this->setting_sources[$section])) {
			throw new SiteException(sprintf(
				"Setting '%s' does not exist in the section '%s'.",
				$name, $section));
		}

		$valid_sources = array(
			self::SOURCE_DEFAULT,
			self::SOURCE_FILE,
			self::SOURCE_DATABASE,
			self::SOURCE_INSTANCE,
			self::SOURCE_RUNTIME,
		);

		if (!in_array($source, $valid_sources)) {
			$source = self::SOURCE_RUNTIME;
		}

		$this->setting_sources[$section][$name] = $source;
	}

	// }}}
	// {{{ protected function loadFileValues()

	/**
	 * Loads config setting values from the ini file
	 */
	protected function loadFileValues()
	{
		$ini_array = parse_ini_file($this->config_filename, true);
		foreach ($ini_array as $section_name => $section_values) {
			if (array_key_exists($section_name, $this->definitions)) {

				// only include values that are defined
				$defined_section_values = array();
				foreach ($section_values as $name => $value) {
					if (array_key_exists($name,
						$this->definitions[$section_name])) {

						$defined_section_values[$name] = $value;
					}
				}

				// merge default fields and values with loaded values
				$defined_section_values = array_merge(
					$this->definitions[$section_name], $defined_section_values);

				$section = new SiteConfigSection($section_name,
					$defined_section_values, $this);

				$this->sections[$section_name] = $section;
			}
		}
	}

	// }}}
	// {{{ protected function loadDatabaseValues()

	/**
	 * Loads config setting values from the database
	 *
	 * Setting values are loaded from the <em>ConfigSetting</em> table.
	 */
	protected function loadDatabaseValues()
	{
		// if there is no database module, do nothing
		if (!$this->app->hasModule('SiteDatabaseModule'))
			return;

		$database = $this->app->getModule('SiteDatabaseModule');
		$db = $database->getConnection();

		$db->loadModule('Manager');
		$tables = $db->manager->listTables();
		if (in_array('configsetting', $tables)) {
			$sql = 'select * from ConfigSetting';
			$rows = SwatDB::query($db, $sql);
			foreach ($rows as $row) {
				if ($row->value !== null) {
					$qualified_name = $row->name;

					if (strpos($qualified_name, '.') === false) {
						throw new SiteException(sprintf(
							"Name of configuration setting '%s' must be ".
							"fully qualifed and of the form section.name.",
							$qualified_name));
					}

					list($section, $name) = explode('.', $qualified_name, 2);

					// skip sections not included in the current configuration
					if (!array_key_exists($section, $this->sections))
						continue;

					$this->$section->$name = $row->value;

					$this->setSource($section, $name, self::SOURCE_DATABASE);
				}
			}
		}
	}

	// }}}
	// {{{ protected function loadInstanceValues()

	/**
	 * Loads instance config setting values from the database
	 *
	 * Setting values are loaded from the <em>InstanceConfigSetting</em> table.
	 */
	protected function loadInstanceValues()
	{
		$instance = $this->app->getInstance();

		// if there is no instance, do nothing
		if ($instance === null)
			return;

		foreach ($instance->config_settings as $setting) {
			if ($setting->value !== null) {
				$qualified_name = $setting->name;

				if (strpos($qualified_name, '.') === false) {
					throw new SiteException(sprintf(
						"Name of configuration setting '%s' must be ".
						"fully qualifed and of the form section.name.",
						$qualified_name));
				}

				list($section, $name) = explode('.', $qualified_name, 2);

				// skip sections not included in the current configuration
				if (!array_key_exists($section, $this->sections))
					continue;

				$this->$section->$name = $setting->value;

				$this->setSource($section, $name, self::SOURCE_INSTANCE);
			}
		}
	}

	// }}}
	// {{{ protected function saveDatabaseValues()

	/**
	 * Saves all configuration values in this config module to the database
	 *
	 * Values are saved in the <em>ConfigSetting</em> table.
	 *
	 * @param array $settings An array of config settings to save.
	 */
	protected function saveDatabaseValues(array $settings)
	{
		// if there is no database module, do nothing
		if (!$this->app->hasModule('SiteDatabaseModule'))
			return;

		$database = $this->app->getModule('SiteDatabaseModule');
		$db = $database->getConnection();

		$transaction = new SwatDBTransaction($db);
		try {
			foreach ($settings as $setting) {
				$sql = sprintf('delete from ConfigSetting
					where name = %s',
					$db->quote($setting, 'text'));

				SwatDB::exec($db, $sql);

				list($section, $name) = explode('.', $setting, 2);
				if ($this->$section->$name != '') {
					$sql = sprintf('insert into ConfigSetting
						(name, value) values (%s, %s)',
						$db->quote($setting, 'text'),
						$db->quote($this->$section->$name, 'text'));

					SwatDB::exec($db, $sql);
				}
			}
			$transaction->commit();
		} catch (SwatDBException $e) {
			$transaction->rollback();
			throw $e;
		}
	}

	// }}}
	// {{{ protected function saveInstanceValues()

	/**
	 * Saves all configuration values in this config module to the database
	 *
	 * Values are saved in the <em>InstanceConfigSetting</em> table with a
	 * binding to the current site instance.
	 *
	 * @param array $settings An array of config settings to save.
	 */
	protected function saveInstanceValues(array $settings)
	{
		$instance = $this->app->instance->getId();

		$database = $this->app->getModule('SiteDatabaseModule');
		$db = $database->getConnection();

		$transaction = new SwatDBTransaction($db);
		try {
			foreach ($settings as $setting) {
				$sql = sprintf('delete from InstanceConfigSetting
					where instance = %s and name = %s',
					$db->quote($instance, 'integer'),
					$db->quote($setting, 'text'));

				SwatDB::exec($db, $sql);

				list($section, $name) = explode('.', $setting, 2);
				if ($this->$section->$name != '') {
					$sql = sprintf('insert into InstanceConfigSetting
						(name, value, instance) values (%s, %s, %s)',
						$db->quote($setting, 'text'),
						$db->quote($this->$section->$name, 'text'),
						$db->quote($instance, 'integer'));

					SwatDB::exec($db, $sql);
				}
			}

			$transaction->commit();
		} catch (SwatDBException $e) {
			$transaction->rollback();
			throw $e;
		}
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
		if (!array_key_exists($name, $this->sections)) {
			throw new SiteException(sprintf(
				"Section '%s' does not exist in this config module.",
				$name));
		}

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
