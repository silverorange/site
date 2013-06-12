<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteMediaType.php';

/**
 * A recordset wrapper class for SiteMediaType objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @see       SiteMediaType
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaTypeWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('SiteMediaType');

		$this->index_field = 'id';
	}

	// }}}
}

?>
