<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'Site/dataobjects/SiteCdnTask.php';
require_once 'Site/dataobjects/SiteImage.php';
require_once 'Site/dataobjects/SiteImageDimension.php';
require_once 'Site/exceptions/SiteCdnException.php';

/**
 * A task that should be performed on a CDN in the near future
 *
 * @package   Site
 * @copyright 2010-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteImageCdnTask extends SiteCdnTask
{
	// public methods
	// {{{ public function getAttemptDescription()

	public function getAttemptDescription()
	{
		switch ($this->operation) {
		case 'copy':
		case 'update':
			$attempt = sprintf(
				Site::_('Updating the dimension ‘%s’ of image ‘%s’ ... '),
				$this->dimension->shortname,
				$this->image->id);

			break;

		default:
			$attempt = sprintf($this->getAttemptDescriptionString(),
				Site::_('image'),
				$this->getInternalValue('image'),
				$this->file_path,
				$this->operation);
		}

		return $attempt;
	}

	// }}}

	// protected methods
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('image',
			SwatDBClassMap::get('SiteImage'));

		$this->registerInternalProperty('dimension',
			SwatDBClassMap::get('SiteImageDimension'));

		$this->table = 'ImageCdnQueue';
	}

	// }}}
	// {{{ protected function copy()

	protected function copy(SiteCdnModule $cdn)
	{
		if ($this->hasImageAndDimension()) {
			$shortname = $this->dimension->shortname;

			// Perform all DB actions first. That way we can roll them back if
			// anything goes wrong with the CDN operation.
			$this->image->setOnCdn(true, $shortname);

			$headers = $this->image->getHttpHeaders($shortname);

			if (strlen($this->override_http_headers)) {
				$headers = array_merge(
					$headers, unserialize($this->override_http_headers)
				);
			}

			$cdn->copyFile(
				$this->image->getUriSuffix($shortname),
				$this->image->getFilePath($shortname),
				$headers,
				$this->getAccessType()
			);
		}
	}

	// }}}
	// {{{ protected function remove()

	protected function remove(SiteCdnModule $cdn)
	{
		// Perform all DB actions first. That way we can roll them back if
		// anything goes wrong with the CDN operation.
		if ($this->hasImageAndDimension()) {
			$this->image->setOnCdn(false, $this->dimension->shortname);
		}

		$cdn->removeFile(
			$this->file_path
		);
	}

	// }}}

	// helper methods
	// {{{ protected function hasImageAndDimension()

	protected function hasImageAndDimension()
	{
		return (($this->image instanceof SiteImage) &&
			($this->dimension instanceof SiteImageDimension));
	}

	// }}}
	// {{{ protected function getAccessType()

	protected function getAccessType()
	{
		return 'public';
	}

	// }}}
}

?>
