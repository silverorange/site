<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/dataobjects/SiteAccountLoginTag.php';

/**
 * A recordset wrapper class for SiteAccountLoginTag objects
 *
 * @package   Site
 * @copyright 2012 silverorange
 * @see       SiteAccountLoginTag
 */
class SiteAccountLoginTagWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('SiteAccountLoginTag');

		$this->index_field = 'id';
	}

	// }}}
}

?>
