<?php

/**
 * A recordset wrapper class for SiteVideoMedia objects
 *
 * @package   Site
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteVideoMedia
 */
class SiteVideoMediaWrapper extends SiteMediaWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get(SiteVideoMedia::class);
	}

	// }}}
	// {{{ protected function getMediaSetWrapperClass()

	protected function getMediaSetWrapperClass()
	{
		return SwatDBClassMap::get(SiteVideoMediaSetWrapper::class);
	}

	// }}}
	// {{{ protected function getMediaEncodingBindingWrapperClass()

	protected function getMediaEncodingBindingWrapperClass()
	{
		return SwatDBClassMap::get(SiteVideoMediaEncodingBindingWrapper::class);
	}

	// }}}
	// {{{ protected function getMediaEncodingBindingOrderBy()

	protected function getMediaEncodingBindingOrderBy()
	{
		// order by width with nulls first so that encodings are ordered from
		// audio (no width), then from smallest to largest encoding.
		return 'media, width asc nulls first';
	}

	// }}}
}

?>
