<?php

require_once 'Site/dataobjects/SiteVideoMediaEncodingBindingWrapper.php';
require_once 'Site/dataobjects/SiteBotrMediaEncodingBinding.php';

/**
 * A recordset wrapper class for SiteBotrMediaEncodingBinding objects
 *
 * @package   Site
 * @copyright 2011-2013 silverorange
 * @see       SiteBotrMediaEncodingBinding
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaEncodingBindingWrapper
	extends SiteVideoMediaEncodingBindingWrapper
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
