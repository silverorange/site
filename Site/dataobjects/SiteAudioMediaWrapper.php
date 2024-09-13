<?php

/**
 * A recordset wrapper class for SiteAudioMedia objects
 *
 * @package   Site
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAudioMedia
 */
class SiteAudioMediaWrapper extends SiteMediaWrapper
{


	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get(SiteAudioMedia::class);
	}


}

?>
