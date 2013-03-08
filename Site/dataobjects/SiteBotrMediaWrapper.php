<?php

require_once 'Site/dataobjects/SiteVideoMediaWrapper.php';
require_once 'Site/dataobjects/SiteBotrMediaSetWrapper.php';
require_once 'Site/dataobjects/SiteBotrMediaEncodingBindingWrapper.php';

/**
 * A recordset wrapper class for SiteBotrMedia objects
 *
 * @package   Site
 * @copyright 2011-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteBotrMedia
 */
class SiteBotrMediaWrapper extends SiteVideoMediaWrapper
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
}

?>
