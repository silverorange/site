<?php

/**
 * Container for layout properties
 *
 * @package   Site
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteLayoutData extends SiteObject
{
	// {{{ private properties

	private $_properties = array();

	// }}}
	// {{{ public function display()

	public function display($template_class)
	{
		$template = new $template_class();
		$template->display($this);
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
