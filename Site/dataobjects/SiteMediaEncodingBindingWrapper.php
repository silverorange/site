<?php

/**
 * A recordset wrapper class for SiteMediaEncodingBinding objects
 *
 * Note: This recordset automatically loads media and types for encoding
 *       bindings when constructed from a database result. If this behaviour is
 *       undesirable, set the lazy_load option to true.
 *
 * @package   Site
 * @copyright 2011-2016 silverorange
 * @see       SiteMediaEncodingBinding
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaEncodingBindingWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function initializeFromResultSet()

	public function initializeFromResultSet(MDB2_Result_Common $rs)
	{
		parent::initializeFromResultSet($rs);

		if (!$this->getOption('lazy_load')) {
			$this->loadAllSubDataObjects(
				'media_type',
				$this->db,
				'select * from MediaType where id in (%s)',
				SwatDBClassMap::get('SiteMediaTypeWrapper')
			);
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
