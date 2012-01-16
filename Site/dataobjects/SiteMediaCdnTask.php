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
			$attempt = sprintf(
				Site::_('Copying the ‘%s’ encoding of media ‘%s’ ... '),
				$this->encoding->shortname,
				$this->media->id);

			break;

		case 'update':
			$attempt = sprintf(Site::_(
				'Updating metadata for the ‘%s’ encoding of media ‘%s’ ... '),
				$this->encoding->shortname,
				$this->media->id);

			break;

		default:
			$attempt = sprintf($this->getAttemptDescriptionString(),
				Site::_('image'),
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
	// {{{ protected function copyItem()

	protected function copyItem(SiteCdnModule $cdn)
	{
		if ($this->hasMediaAndEncoding()) {
			// Perform all DB actions first. That way we can roll them back if
			// anything goes wrong with the CDN operation.
			$this->media->setOnCdn(true, $this->encoding->shortname);

			$binding = $this->media->getEncodingBinding(
				$this->encoding->shortname);

			$cdn->copyFile(
				$this->media->getFilePath($this->encoding->shortname),
				$this->media->getUriSuffix($this->encoding->shortname),
				$binding->media_type->mime_type,
				$this->getAccessType(),
				$this->getHttpHeaders());
		}

		return true;
	}

	// }}}
	// {{{ protected function updateItemMetadata()

	protected function updateItemMetadata(SiteCdnModule $cdn)
	{
		if ($this->hasMediaAndEncoding()) {
			$binding = $this->media->getEncodingBinding(
				$this->encoding->shortname);

			$cdn->updateFileMetadata(
				$this->media->getUriSuffix($this->encoding->shortname),
				$binding->media_type->mime_type,
				$this->getAccessType(),
				$this->getHttpHeaders());
		}

		return true;
	}

	// }}}
	// {{{ protected function deleteItem()

	protected function deleteItem(SiteCdnModule $cdn)
	{
		// Perform all DB actions first. That way we can roll them back if
		// anything goes wrong with the CDN operation.
		if ($this->hasMediaAndEncoding()) {
			$this->media->setOnCdn(false, $this->encoding->shortname);
		}

		return parent::deleteItem($cdn);
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
	// {{{ protected function getAccessType()

	protected function getAccessType()
	{
		return 'private';
	}

	// }}}
	// {{{ protected function getHttpHeaders()

	protected function getHttpHeaders()
	{
		$headers = parent::getHttpHeaders();

		$headers['content-disposition'] = sprintf('attachment; filename="%s"',
			$this->media->getContentDispositionFilename(
				$this->encoding->shortname));

		return $headers;
	}

	// }}}
}

?>
