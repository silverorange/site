<?php

require_once 'Site/dataobjects/SiteMediaEncodingBinding.php';

/**
 * A video-specific media encoding binding object
 *
 * @package   Site
 * @copyright 2011-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteVideoMediaEncodingBinding extends SiteMediaEncodingBinding
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

	// }}}
}

?>
