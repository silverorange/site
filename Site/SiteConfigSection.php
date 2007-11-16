<?php

require_once 'Swat/SwatObject.php';
require_once 'Site/exceptions/SiteException.php';

/**
 * Configuration section for the configuration module
 *
 * @package   Site
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteConfigSection extends SwatObject
{
	// {{{ private properties

	/**
	 * The name of this configuration section
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Settings of this configuration section
	 */
	private $values = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new configuration section
	 *
	 * @param string $name the name of this configuration section.
	 * @param array $values an associative array containing values as parsed
	 *                       from an ini file.
	 */
	public function __construct($name, array $values)
	{
		$this->name = (string)$name;
		$this->values = $values;
	}

	// }}}
	// {{{ private function __set()

	/**
	 * Sets a setting of this configuration section
	 *
	 * @param string $name the name of the setting.
	 * @param mixed $value the value of the setting.
	 *
	 * @throws SiteException if the name of the setting being set does not
	 *                       exist in this section.
	 */
	private function __set($name, $value)
	{
		if (!array_key_exists($name, $this->values))
			throw new SiteException(
				sprintf("Can not set configuration setting. Setting '%s' ".
					"does not exist in the section '%s'.",
				$name, $this->name));

		$this->values[$name] = $value;
	}

	// }}}
	// {{{ private function __get()

	/**
	 * Gets a settion of this configuration section
	 *
	 * @param string $name the name of the setting.
	 *
	 * @return mixed the value of the configuration setting.
	 *
	 * @throws SiteException if the setting being set does not exist in this
	 *                       section.
	 */
	private function __get($name)
	{
		if (!array_key_exists($name, $this->values))
			throw new SiteException(
				sprintf("Can not get configuration setting. Setting '%s' ".
					"does not exist in the section '%s'.",
				$name, $this->name));

		return $this->values[$name];
	}

	// }}}
	// {{{ private function __isset()

	/**
	 * Checks for existence of a configuration setting in this section
	 *
	 * @param string $name the name of the configuration setting to check.
	 *
	 * @return boolean true if the configuration setting exists and false if it
	 *                  does not.
	 */
	private function __isset($name)
	{
		return array_key_exists($name, $this->values);
	}

	// }}}
}

?>
