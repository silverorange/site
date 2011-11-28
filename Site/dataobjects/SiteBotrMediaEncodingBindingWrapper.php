<?php

require_once 'Site/dataobjects/SiteMediaEncodingBindingWrapper.php';
require_once 'Site/dataobjects/SiteBotrMediaEncodingBinding.php';

/**
 * A recordset wrapper class for SiteMediaEncodingBinding objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @see       SiteBotrMediaEncodingBinding
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaEncodingBindingWrapper
	extends SiteMediaEncodingBindingWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('SiteBotrMediaEncodingBinding');
	}

	// }}}
}

?>
