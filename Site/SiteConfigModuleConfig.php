<?php

require_once 'Swat/SwatObject.php';

/**
 * Configuration object for the configuration module
 *
 * @package   Site
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteConfigModuleConfig extends SwatObject
{
	// {{{ private properties

	/**
	 * Settings of this configuration
	 */
	private $values = array();

	// }}}
	// {{{ private function __set()

	/**
	 * Sets a setting of this configuration
	 *
	 * @param string $name the name of the setting.
	 * @param mixed $value the value of the setting.
	 */
	private function __set($name, $value)
	{
		$this->values[$name] = $value;
	}

	// }}}
	// {{{ private function __get()

	/**
	 * Gets a value of this configuration 
	 *
	 * @param string $name the name of the value.
	 *
	 * @return mixed the value of the configuration setting or null if the
	 *                configuration setting is not set.
	 */
	private function __get($name)
	{
		$value = null;
		if (isset($this->values[$name]))
			$value = $this->values[$name];

		return $value;
	}

	// }}}
	// {{{ private function __isset()

	/**
	 * Checks for existence of a configuration setting
	 *
	 * @param string $name the name of the configuration setting to check.
	 *
	 * @return boolean true if the configuration setting exists and false if it
	 *                  does not.
	 */
	private function __isset($name)
	{
		return isset($this->values[$name]);
	}

	// }}}
}

?>
