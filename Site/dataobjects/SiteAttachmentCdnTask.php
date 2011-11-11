<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'Site/dataobjects/SiteCdnTask.php';
require_once 'Site/dataobjects/SiteAttachment.php';

/**
 * An attachment task that should be preformed to a CDN in the near future
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAttachmentCdnTask extends SiteCdnTask
{
	// {{{ private properties

	/**
	 * Whether or not this task was successfully completed
	 *
	 * @var boolean
	 */
	private $success = false;

	// }}}

	// public methods
	// {{{ public function run()

	public function run(SiteCdnModule $cdn)
	{
		switch ($this->operation) {
		case 'copy':
			$this->copyAttachment($cdn);
			break;
		case 'delete':
			$this->deleteAttachment($cdn);
			break;
		default:
			$this->error();
			break;
		}
	}

	// }}}
	// {{{ public function getAttemptDescription()

	public function getAttemptDescription()
	{
		switch ($this->operation) {
		case 'copy':
			$attempt = sprintf(
				Site::_('Copying attachment %s ... '),
				$this->attachment->id);

			break;
		case 'delete':
			$attempt = sprintf(
				Site::_('Deleting ‘%s’ ... '),
				$this->file_path);

			break;
		default:
			$attempt = sprintf(
				Site::_('Unknown operation ‘%s’ ... '),
				$this->operation);
		}

		return $attempt;
	}

	// }}}
	// {{{ public function getResultDescription()

	public function getResultDescription()
	{
		return (($this->success) ? Site::_('done.') : Site::_('error.'))."\n";
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
	// {{{ protected function error()

	protected function error()
	{
		$this->success = false;

		$this->error_date = new SwatDate();
		$this->error_date->toUTC();
		$this->save();
	}

	// }}}
	// {{{ protected function copyAttachment()

	protected function copyAttachment(SiteCdnModule $cdn)
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
		} catch (SwatFileNotFoundException $e) {
			$transaction->rollback();
			$e->processAndContinue();
			$this->error();
		} catch (SiteCdnException $e) {
			$transaction->rollback();
			$e->processAndContinue();
			$this->error();
		}
	}

	// }}}
	// {{{ protected function deleteAttachment()

	protected function deleteAttachment(SiteCdnModule $cdn)
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
		} catch (SwatFileNotFoundException $e) {
			$transaction->rollback();
			$e->processAndContinue();
			$this->error();
		} catch (SiteCdnException $e) {
			$transaction->rollback();
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
		$disposition = sprintf('attachment; filename="%s"',
			$this->attachment->getContentDispositionFilename());

		return array(
			'cache-control'       => 'public, max-age=315360000',
			'content-disposition' => $disposition,
		);
	}

	// }}}
}

?>
