<?php

require_once 'Site/dataobjects/SiteImage.php';

/**
 * An image data object for video frame-grabs
 *
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteVideoImage extends SiteImage
{
	// {{{ public function getUri()

    public function getUri($shortname = '720', $prefix = null)
	{
		return parent::getUri($shortname, $prefix);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->image_set_shortname = 'videos';
	}

	// }}}
}

?>
