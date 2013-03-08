<?php

require_once 'Site/dataobjects/SiteMediaSet.php';
require_once 'Site/dataobjects/SiteVideoMediaEncodingWrapper.php';

/**
 * A video-specific media set object
 *
 * @package   Site
 * @copyright 2011-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteVideoMediaSet extends SiteMediaSet
{
	// {{{ public properties

	public $skin = null;

	// }}}

	// loader methods
	// {{{ protected function getMediaEncodingWrapperClass()

	protected function getMediaEncodingWrapperClass()
	{
		return SwatDBClassMap::get('SiteVideoMediaEncodingWrapper');
	}

	// }}}
	// {{{ protected function getMediaEncodingOrderBy()

	protected function getMediaEncodingOrderBy()
	{
		return 'width desc';
	}

	// }}}
}

?>
