<?php


/**
 * A recordset wrapper class for SiteBotrMediaSet objects
 *
 * @package   Site
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteBotrMediaSet
 */
class SiteBotrMediaSetWrapper extends SiteVideoMediaSetWrapper
{
	// {{{ protected function getMediaEncodingWrapperClass()

	protected function getMediaEncodingWrapperClass()
	{
		return SwatDBClassMap::get('SiteBotrMediaEncodingWrapper');
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
