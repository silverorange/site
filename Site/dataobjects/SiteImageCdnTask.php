<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteCdnTask.php';
require_once 'Site/dataobjects/SiteImage.php';
require_once 'Site/dataobjects/SiteImageDimension.php';

/**
 * A task that should be preformed to a CDN in the near future
 *
 * @package   Site
 * @copyright 2010-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteImageCdnTask extends SiteCdnTask
{
	// {{{ public function setOnCdn()

	public function setOnCdn($on_cdn = true)
	{
		if ($this->image instanceof SiteImage) {
			$this->image->setOnCdn($on_cdn, $this->dimension->shortname);
		}
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('image',
			SwatDBClassMap::get('SiteImage'));

		$this->registerInternalProperty('dimension',
			SwatDBClassMap::get('SiteImageDimension'));

		$this->table    = 'ImageCdnQueue';
	}

	// }}}
}

?>
