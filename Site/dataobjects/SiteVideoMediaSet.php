<?php

/**
 * A video-specific media set object
 *
 * @package   Site
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteVideoMediaSet extends SiteMediaSet
{


	public $skin = null;



	// loader methods


	protected function getMediaEncodingWrapperClass()
	{
		return SwatDBClassMap::get(SiteVideoMediaEncodingWrapper::class);
	}




	protected function getMediaEncodingOrderBy()
	{
		return 'width desc';
	}


}

?>
