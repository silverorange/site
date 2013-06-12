<?php

require_once 'Site/SiteObject.php';
require_once 'Site/exceptions/SiteInvalidPropertyException.php';

/**
 * Container for layout properties
 *
 * @package   Site
 * @copyright 2006-2009 silverorange
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
		require $filename;
	}

	// }}}
	// {{{ public function exists()

	/**
	 * @deprecated use the isset() function on this class instead.
	 */
	public function exists($name)
	{
		return isset($this->$name);
	}

	// }}}
	// {{{ public function __isset()

	public function __isset($name)
	{
		return isset($this->_properties[$name]);
	}

	// }}}
	// {{{ public function __get()

	/**
	 * @throws SiteInvalidPropertyException
	 */
	public function __get($name)
	{
		if (!isset($this->_properties[$name]))
			throw new SiteInvalidPropertyException(
				"There is no content available for '{$name}'.",
				0, $this, $name);

		return $this->_properties[$name];
	}

	// }}}
	// {{{ public function __set()

	public function __set($name, $content)
	{
		$this->_properties[$name] = $content;
	}

	// }}}
}

?>
