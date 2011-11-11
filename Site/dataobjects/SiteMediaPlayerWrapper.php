<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteMediaPlayer.php';

/**
 * A recordset wrapper class for SiteMediaPlayer objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @see       SiteMediaPlayer
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaPlayerWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('SiteMediaPlayer');

		$this->index_field = 'id';
	}

	// }}}
}

?>
