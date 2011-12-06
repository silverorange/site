<?php

require_once 'Site/dataobjects/SiteMediaSetWrapper.php';
require_once 'Site/dataobjects/SiteBotrMediaSet.php';

/**
 * A recordset wrapper class for SiteBotrMediaSet objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteBotrMediaSet
 */
class SiteBotrMediaSetWrapper extends SiteMediaSetWrapper
{
	// {{{ protected function getMediaEncodingWrapperClass()

	protected function getMediaEncodingWrapperClass()
	{
		return SwatDBClassMap::get('SiteBotrMediaEncodingWrapper');
	}

	// }}}
	// {{{ protected function getMediaEncodingOrderBy()

	protected function getMediaEncodingOrderBy()
	{
		return 'media_set, width desc';
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('SiteBotrMediaSet');
	}

	// }}}
}

?>
