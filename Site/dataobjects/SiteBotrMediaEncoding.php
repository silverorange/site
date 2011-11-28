<?php

require_once 'Site/dataobjects/SiteMediaEncoding.php';

/**
 * A BOTR-specific media encoding object
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaEncoding extends SiteMediaEncoding
{
	// {{{ public properties

	/**
	 * BOTR key
	 *
	 * @var string
	 */
	public $key;

	/**
	 * Width in pixels
	 *
	 * @var integer
	 */
	public $width;

	// }}}
}

?>
