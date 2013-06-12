<?php

require_once 'Site/dataobjects/SiteImage.php';

/**
 * An image data object for video-scrubber thumbnail images
 *
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteVideoScrubberImage extends SiteImage
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->image_set_shortname = 'video-scrubber';
	}

	// }}}
}

?>
