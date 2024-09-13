<?php

/**
 * An image data object for video-scrubber thumbnail images.
 *
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteVideoScrubberImage extends SiteImage
{
    protected function init()
    {
        parent::init();

        $this->image_set_shortname = 'video-scrubber';
    }
}
