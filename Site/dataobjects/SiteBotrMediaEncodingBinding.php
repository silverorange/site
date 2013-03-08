<?php

require_once 'Site/dataobjects/SiteVideoMediaEncodingBinding.php';

/**
 * A BOTR-specific media encoding binding object
 *
 * @package   Site
 * @copyright 2011-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaEncodingBinding extends SiteVideoMediaEncodingBinding
{
	// {{{ public properties

	/**
	 * BOTR key
	 *
	 * @var string
	 */
	public $key;

	// }}}
}

?>
