<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteGadgetInstance.php';

/**
 * A cache for a particular gadget instance
 *
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteGadgetCache extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The name of the cache
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The value of the cache
	 *
	 * @var string
	 */
	public $value;

	/**
	 * The last time the cache was updated
	 *
	 * @var SwatDate
	 */
	public $last_update;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'GadgetCache';
		$this->id_field = 'integer:id';
		$this->registerInternalProperty('gadget_instance',
			'SiteGadgetInstance');

		$this->registerDateProperty('last_update');
	}

	// }}}
}

?>
