<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteMediaSet.php';
require_once 'Site/dataobjects/SiteMediaEncodingWrapper.php';

/**
 * A recordset wrapper class for SiteMediaSet objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @see       SiteMediaSet
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaSetWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function __construct()

	public function __construct($recordset = null)
	{
		parent::__construct($recordset);

		if ($recordset !== null) {
			$this->loadAllSubRecordsets(
				'encodings',
				$this->getEncodingWrapper(),
				'MediaEncoding',
				'media_set',
				'',
				$this->getEncodingsOrderBy());
		}
	}

	// }}}
	// {{{ protected function getEncodingWrapper()

	protected function getEncodingWrapper()
	{
		return SwatDBClassMap::get('SiteMediaEncodingWrapper');
	}

	// }}}
	// {{{ protected function getEncodingsOrderBy()

	protected function getEncodingsOrderBy()
	{
		return 'media_set';
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('SiteMediaSet');

		$this->index_field = 'id';
	}

	// }}}
}

?>
