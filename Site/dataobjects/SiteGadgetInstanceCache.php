<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A cache that belongs to a gadget instance.
 *
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteGadgetInstance
 */
class SiteGadgetInstanceCache extends SwatDBDataobject
{
	// {{{ public properties

	/**
	 * The unique id of this cache
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The gadget instance this cahce belongs too
	 *
	 * @var integer
	 */
	public $gadget_instance;

	/**
	 * The value of this cache
	 *
	 * @var string
	 */
	public $value;

	/**
	 * The last time this cache was updated
	 *
	 * @var SwatDate
	 */
	public $last_update;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'GadgetInstanceCache';
		$this->id_field = 'integer:id';

		$this->registerDateProperty('last_update');
	}

	// }}}
}

?>
