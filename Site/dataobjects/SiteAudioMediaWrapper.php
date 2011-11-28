<?php

require_once 'Site/dataobjects/SiteMediaWrapper.php';
require_once 'Site/dataobjects/SiteAudioMedia.php';

/**
 * A recordset wrapper class for SiteAudioMedia objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAudioMedia
 */
class SiteAudioMediaWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('SiteAudioMedia');
	}

	// }}}
}

?>
