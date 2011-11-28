<?php

require_once 'Site/dataobjects/SiteBotrMediaEncoding.php';

/**
 * A recordset wrapper class for MediaEncoding objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @see       SiteBotrMediaEncoding
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaEncodingWrapper extends SiteMediaEncodingWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('SiteBotrMediaEncoding');
	}

	// }}}
}

?>
