<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteInstanceConfigSetting.php';

/**
 * A recordset wrapper class for SiteInstanceConfigSetting objects
 *
 * @package   Site
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteInstanceConfigSettingWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			SwatDBClassMap::get('SiteInstanceConfigSetting');
	}

	// }}}
}

?>
