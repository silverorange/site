<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteMediaEncodingBinding.php';
require_once 'Site/dataobjects/SiteMediaTypeWrapper.php';

/**
 * A recordset wrapper class for SiteMediaEncodingBinding objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @see       SiteMediaEncodingBinding
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaEncodingBindingWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function __construct()

	public function __construct($recordset = null)
	{
		parent::__construct($recordset);

		if ($recordset !== null) {
			$this->loadAllSubDataObjects(
				'media_type',
				$this->db,
				'select * from MediaType where id in (%s)',
				SwatDBClassMap::get('SiteMediaTypeWrapper'));
		}
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('SiteMediaEncodingBinding');

		$this->index_field = 'media_encoding';
	}

	// }}}
}

?>
