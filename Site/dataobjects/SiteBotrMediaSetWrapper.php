<?php

require_once 'Site/dataobjects/SiteMediaSetWrapper.php';

/**
 * A recordset wrapper class for SiteBotrMediaSet objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteBotrMediaSet
 */
class SiteBotrMediaSetWrapper extends SiteMediaSetWrapper
{
	// {{{ protected function getEncodingWrapper()

	protected function getEncodingWrapper()
	{
		return SwatDBClassMap::get('SiteBotrMediaEncodingWrapper');
	}

	// }}}
	// {{{ protected function getEncodingsOrderBy()

	protected function getEncodingsOrderBy()
	{
		return 'media_set, width desc';
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
