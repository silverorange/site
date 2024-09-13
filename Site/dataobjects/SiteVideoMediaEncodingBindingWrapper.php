<?php

/**
 * A recordset wrapper class for SiteVideoMediaEncodingBinding objects
 *
 * @package   Site
 * @copyright 2011-2016 silverorange
 * @see       SiteVideoMediaEncodingBinding
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteVideoMediaEncodingBindingWrapper extends
	SiteMediaEncodingBindingWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get(SiteVideoMediaEncodingBinding::class);
	}

	// }}}
}

?>
