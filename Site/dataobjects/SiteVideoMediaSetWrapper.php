<?php

require_once 'Site/dataobjects/SiteMediaSetWrapper.php';
require_once 'Site/dataobjects/SiteVideoMediaSet.php';

/**
 * A recordset wrapper class for SiteVideoMediaSet objects
 *
 * @package   Site
 * @copyright 2011-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteVideoMediaSet
 */
class SiteVideoMediaSetWrapper extends SiteMediaSetWrapper
{
	// {{{ protected function getMediaEncodingWrapperClass()

	protected function getMediaEncodingWrapperClass()
	{
		return SwatDBClassMap::get('SiteVideoMediaEncodingWrapper');
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

		$this->row_wrapper_class = SwatDBClassMap::get('SiteVideoMediaSet');
	}

	// }}}
}

?>
