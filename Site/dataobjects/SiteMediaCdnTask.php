<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'Site/dataobjects/SiteCdnTask.php';
require_once 'Site/dataobjects/SiteMedia.php';
require_once 'Site/dataobjects/SiteMediaEncoding.php';
require_once 'Site/exceptions/SiteCdnException.php';

/**
 * A task that should be preformed to a CDN in the near future
 *
 * @package   Site
 * @copyright 2011-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaCdnTask extends SiteCdnTask
{
	// public methods
	// {{{ public function getAttemptDescription()

	public function getAttemptDescription()
	{
		switch ($this->operation) {
		case 'copy':
		case 'update':
			$attempt = sprintf(
				Site::_('Updating the ‘%s’ encoding of media ‘%s’ ... '),
				$this->encoding->shortname,
				$this->media->id);

			break;

		default:
			$attempt = sprintf($this->getAttemptDescriptionString(),
				Site::_('media'),
				$this->getInternalValue('media'),
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

		$this->registerInternalProperty('media',
			SwatDBClassMap::get('SiteMedia'));

		$this->registerInternalProperty('encoding',
			SwatDBClassMap::get('SiteMediaEncoding'));

		$this->table = 'MediaCdnQueue';
	}

	// }}}
	// {{{ protected function copy()

	protected function copy(SiteCdnModule $cdn)
	{
		if ($this->hasMediaAndEncoding()) {
			$shortname = $this->encoding->shortname;

			// Perform all DB actions first. That way we can roll them back if
			// anything goes wrong with the CDN operation.
			$this->media->setOnCdn(true, $shortname);

			$headers = $this->media->getHttpHeaders($shortname);

			if (strlen($this->override_http_headers)) {
				$headers = array_merge(
					$headers, unserialize($this->override_http_headers)
				);
			}

			$cdn->copyFile(
				$this->media->getUriSuffix($shortname),
				$this->media->getFilePath($shortname),
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
		if ($this->hasMediaAndEncoding()) {
			$this->media->setOnCdn(false, $this->encoding->shortname);
		}

		$cdn->removeFile(
			$this->file_path
		);
	}

	// }}}

	// helper methods
	// {{{ protected function hasMediaAndEncoding()

	protected function hasMediaAndEncoding()
	{
		return (($this->media instanceof SiteMedia) &&
			($this->encoding instanceof SiteMediaEncoding));
	}

	// }}}
}

?>
