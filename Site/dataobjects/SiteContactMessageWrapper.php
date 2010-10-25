<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteContactMessage.php';

/**
 * A recordset wrapper class for SiteContactMessage objects
 *
 * @package   Site
 * @copyright 2010 silverorange
 * @see       SiteContactMessage
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteContactMessageWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('SiteContactMessage');
		$this->index_field = 'id';
	}

	// }}}
}

?>
