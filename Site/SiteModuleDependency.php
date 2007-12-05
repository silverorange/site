<?php

require_once 'Swat/SwatObject.php';
require_once 'Site/exceptions/SiteException.php';

/**
 * Dependencies for site modules
 *
 * @package   Site
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see SiteApplicationModule
 */
class SiteModuleDependency extends SwatObject
{
	// {{{ public properties

	/**
	 * The class name of the dependent module
	 *
	 * @var string
	 */
	public $class_name;

	/**
	 * Whether the dependent module is required or optional
	 */
	public $required = true;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new module dependency
	 *
	 * @param string $class_name the class name of the dependent module.
	 * @param boolean $required whether the class name is required or not. If
	 *                          required is true, {@link SiteApplication} will
	 *                          verify the existance of the dependent module.
	 */
	public function __construct($class_name, $required = true)
	{
		$this->class_name = (string) $class_name;
		$this->required = $required;
	}

	// }}}
}

?>
