<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/dataobjects/SiteAccountLoginHistory.php';

/**
 * A recordset wrapper class for SiteAccountLoginHistory objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @see       SiteAccountLoginHistory
 */
class SiteAccountLoginHistoryWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('SiteAccountLoginHistory');

		$this->index_field = 'id';
	}

	// }}}
}

?>
