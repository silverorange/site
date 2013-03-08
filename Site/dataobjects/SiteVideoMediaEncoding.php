<?php

require_once 'Site/dataobjects/SiteMediaEncoding.php';
require_once 'Site/dataobjects/SiteVideoMediaSet.php';

/**
 * A video-specific media encoding object
 *
 * @package   Site
 * @copyright 2011-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteVideoMediaEncoding extends SiteMediaEncoding
{
	// {{{ public properties

	/**
	 * Width in pixels
	 *
	 * @var integer
	 */
	public $width;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('media_set',
			SwatDBClassMap::get('SiteVideoMediaSet'));
	}

	// }}}
}

?>
