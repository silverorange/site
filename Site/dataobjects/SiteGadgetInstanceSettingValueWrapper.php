<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteGadgetInstanceSettingValue.php';

/**
 * A recordset wrapper for gadget instance setting values
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteGadgetInstanceSettingValue
 */
class SiteGadgetInstanceSettingValueWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('SiteGadgetInstanceSettingValue');

		$this->index_field = 'name';
	}

	// }}}
}

?>
