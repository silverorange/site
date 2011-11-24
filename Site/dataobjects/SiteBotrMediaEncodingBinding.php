<?php

require_once 'Site/dataobjects/SiteMediaEncodingBinding.php';

/**
 * A BOTR-specific media encoding binding object
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaEncodingBinding extends SiteMediaEncodingBinding
{
	// {{{ public properties

	/**
	 * Width
	 *
	 * @var integer
	 */
	public $width;

	/**
	 * Height
	 *
	 * @var integer
	 */
	public $height;

	/**
	 * BOTR key
	 *
	 * @var string
	 */
	public $key;

	// }}}
}

?>
