<?php

require_once 'Site/SiteObject.php';
require_once 'Site/exceptions/SiteInvalidPropertyException.php';

/**
 * Base class for a layout
 *
 * @package   Site
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteLayout extends SiteObject
{
	// {{{ private properties

	private $_properties = array();
	private $_filename = null;
	
	// }}}
	// {{{ public function __construct()

	public function __construct($filename)
	{
		$this->_filename = $filename;
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
	// {{{ public function setFilename()

	public function setFilename($filename)
	{
		$this->_filename = $filename;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		require_once $this->_filename;
	}

	// }}}
}
?>
