<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteGadgetInstance.php';

/**
 * A recordset wrapper for gadget instances
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteGadgetInstance
 */
class SiteGadgetInstanceWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('SiteGadgetInstance');
		$this->index_field = 'id';
	}

	// }}}
}

?>
