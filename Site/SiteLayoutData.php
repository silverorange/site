<?php

require_once 'Site/SiteObject.php';
require_once 'Site/exceptions/SiteInvalidPropertyException.php';

/**
 * Container for layout properties
 *
 * @package   Site
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteLayoutData extends SiteObject
{
	// {{{ private properties

	private $_properties = array();
	
	// }}}
	// {{{ public function display()

	public function display($filename)
	{
		require_once $filename;
	}

	// }}}
	// {{{ private function __get()

	/**
	 * @throws SiteInvalidPropertyException
	 */
	private function __get($name)
	{
		if (!isset($this->_properties[$name]))
			throw new SiteInvalidPropertyException(
				"There is no content available for '{$name}'.",
				0, $this, $name);

		return $this->_properties[$name];
	}

	// }}}
	// {{{ private function __set()

	private function __set($name, $content)
	{
		$this->_properties[$name] = $content;
	}

	// }}}
	// {{{ public function exists()

	public function exists($name)
	{
		return isset($this->_properties[$name]);
	}

	// }}}
}
?>
