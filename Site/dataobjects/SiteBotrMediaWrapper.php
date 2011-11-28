<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteMediaWrapper.php';
require_once 'Site/dataobjects/SiteBotrMediaSetWrapper.php';
require_once 'Site/dataobjects/SiteBotrMediaEncodingBindingWrapper.php';

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
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('SiteBotrMedia');
	}

	// }}}
	// {{{ protected function getMediaSetWrapperClass()

	protected function getMediaSetWrapperClass()
	{
		return SwatDBClassMap::get('SiteBotrMediaSetWrapper');
	}

	// }}}
	// {{{ protected function getMediaEncodingBindingWrapperClass()

	protected function getMediaEncodingBindingWrapperClass()
	{
		return SwatDBClassMap::get('SiteBotrMediaEncodingBindingWrapper');
	}

	// }}}
	// {{{ protected function getMediaEncodingBindingOrderBy()

	protected function getMediaEncodingBindingOrderBy()
	{
		// order by width with nulls first so that encodings are ordered from
		// audio (no width), then from smallest to largest encoding.
		return 'media, width asc nulls first';
	}

	// }}}
}

?>
