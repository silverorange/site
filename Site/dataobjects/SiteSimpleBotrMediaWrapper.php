<?php

require_once 'Site/dataobjects/SiteBotrMedia.php';
require_once 'Site/dataobjects/SiteSimpleMediaWrapper.php';

/**
 * A simple recordset wrapper class for SiteBotrMedia objects that doesn't
 * do any pre-loading of sub-dataobjects.
 *
 * This is useful for cases where you just want the basic media data
 *
 * @package   Site
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteBotrMedia
 */
class SiteSimpleBotrMediaWrapper extends SiteSimpleMediaWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('SiteBotrMedia');
	}

	// }}}
}

?>
