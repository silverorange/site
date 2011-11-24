<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteBotrMediaPlayer.php';

/**
 * A recordset wrapper class for SiteBotrMediaPlayer objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteBotrMediaPlayer
 */
class SiteBotrMediaPlayerWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('SiteBotrMediaPlayer');

		$this->index_field = 'id';
	}

	// }}}
}

?>
