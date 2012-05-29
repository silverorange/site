<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'Site/dataobjects/SiteCdnTask.php';
require_once 'Site/dataobjects/SiteAttachment.php';
require_once 'Site/exceptions/SiteCdnException.php';

/**
 * An attachment task that should be preformed to a CDN in the near future
 *
 * @package   Site
 * @copyright 2011-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAttachmentCdnTask extends SiteCdnTask
{
	// public methods
	// {{{ public function getAttemptDescription()

	public function getAttemptDescription()
	{
		return sprintf($this->getAttemptDescriptionString(),
			Site::_('attachment'),
			$this->getInternalValue('attachment'),
			$this->file_path,
			$this->operation);
	}

	// }}}

	// protected methods
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('attachment',
			SwatDBClassMap::get('SiteAttachment'));

		$this->table = 'AttachmentCdnQueue';
	}

	// }}}
	// {{{ protected function copy()

	protected function copy(SiteCdnModule $cdn)
	{
		if ($this->hasAttachment()) {
			// Perform all DB actions first. That way we can roll them back if
			// anything goes wrong with the CDN operation.
			$this->attachment->on_cdn = true;
			$this->attachment->save();

			$headers = $this->attachment->getHttpHeaders();

			if (strlen($this->override_http_headers)) {
				$headers = array_merge(
					$headers, unserialize($this->override_http_headers)
				);
			}

			$cdn->copyFile(
				$this->attachment->getUriSuffix(),
				$this->attachment->getFilePath(),
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
		if ($this->hasAttachment()) {
			$this->attachment->on_cdn = false;
			$this->attachment->save();
		}

		$cdn->removeFile(
			$this->file_path
		);
	}

	// }}}

	// helper methods
	// {{{ protected function hasAttachment()

	protected function hasAttachment()
	{
		return ($this->attachment instanceof SiteAttachment);
	}

	// }}}
}

?>
