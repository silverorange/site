<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteMediaWrapper.php';

/**
 * A recordset wrapper class for SiteBotrMedia objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteBotrMedia
 */
class SiteBotrMediaWrapper extends SiteMediaWrapper
{
	// {{{ protected function getEncodingsOrderBy()

	protected function getEncodingsOrderBy()
	{
		// order by width with nulls first so that encodings are ordered from
		// audio (no width), then from smallest to largest encoding.
		return 'media, width asc nulls first';
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('SiteBotrMedia');
	}

	// }}}
}

?>
