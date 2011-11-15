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
 * @copyright 2011 silverorange
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
	// {{{ protected function copyItem()

	protected function copyItem(SiteCdnModule $cdn)
	{
		try {
			$transaction = new SwatDBTransaction($this->db);

			// Perform all DB actions first. That way we can roll them back
			// if anything goes wrong with the CDN operation.
			if ($this->hasAttachment()) {
				$this->attachment->on_cdn = true;
				$this->attachment->save();
			}

			$this->delete();

			if ($this->hasAttachment()) {
				$cdn->copyFile(
					$this->attachment->getFilePath(),
					$this->attachment->getUriSuffix(),
					$this->attachment->mime_type,
					$this->getAccessType(),
					$this->getHttpHeaders());
			}

			$transaction->commit();

			$this->success = true;
		} catch (SwatDBException $e) {
			$transaction->rollback();
			$e->processAndContinue();
		} catch (Exception $e) {
			$transaction->rollback();

			$e = new SiteCdnException($e);
			$e->processAndContinue();
			$this->error();
		}
	}

	// }}}
	// {{{ protected function deleteItem()

	protected function deleteItem(SiteCdnModule $cdn)
	{
		try {
			$transaction = new SwatDBTransaction($this->db);

			// Perform all DB actions first. That way we can roll them back
			// if anything goes wrong with the CDN operation.
			if ($this->hasAttachment()) {
				$this->attachment->on_cdn = false;
				$this->attachment->save();
			}

			$this->delete();

			$cdn->deleteFile($this->file_path);

			$transaction->commit();

			$this->success = true;
		} catch (SwatDBException $e) {
			$transaction->rollback();
			$e->processAndContinue();
		} catch (Exception $e) {
			$transaction->rollback();

			$e = new SiteCdnException($e);
			$e->processAndContinue();
			$this->error();
		}
	}

	// }}}

	// helper methods
	// {{{ protected function hasAttachment()

	protected function hasAttachment()
	{
		return ($this->attachment instanceof SiteAttachment);
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
			$this->attachment->getContentDispositionFilename());

		return $headers;
	}

	// }}}
}

?>
