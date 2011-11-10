<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'Swat/SwatDate.php';
require_once 'Site/dataobjects/SiteCdnTask.php';
require_once 'Site/dataobjects/SiteMedia.php';
require_once 'Site/dataobjects/SiteMediaEncoding.php';

/**
 * A task that should be preformed to a CDN in the near future
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaCdnTask extends SiteCdnTask
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
			$this->copyMedia($cdn);
			break;
		case 'delete':
			$this->deleteMedia($cdn);
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
				Site::_('Copying the %s encoding of media %s ... '),
				$this->encoding->shortname, $this->media->id);

			break;
		case 'delete':
			$attempt = sprintf(Site::_('Deleting ‘%s’ ... '),
				$this->file_path);

			break;
		default:
			$attempt = sprintf(Site::_('Unknown operation ‘%s’ ... '),
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

		$this->registerInternalProperty('media',
			SwatDBClassMap::get('SiteMedia'));

		$this->registerInternalProperty('encoding',
			SwatDBClassMap::get('SiteMediaEncoding'));

		$this->table = 'MediaCdnQueue';
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
	// {{{ protected function copyMedia()

	protected function copyMedia(SiteCdnModule $cdn)
	{
		try {
			$transaction = new SwatDBTransaction($this->db);

			// Perform all DB actions first. That way we can roll them back
			// if anything goes wrong with the CDN operation.
			if ($this->hasMediaAndEncoding()) {
				$this->media->setOnCdn(true, $this->encoding->shortname);
			}

			$this->delete();

			if ($this->hasMediaAndEncoding()) {
				$binding = $this->media->getEncodingBinding(
					$this->encoding->shortname);

				$cdn->copyFile(
					$this->media->getFilePath($this->encoding->shortname),
					$this->media->getUriSuffix($this->encoding->shortname),
					$binding->media_type->mime_type,
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
	// {{{ protected function deleteMedia()

	protected function deleteMedia(SiteCdnModule $cdn)
	{
		try {
			$transaction = new SwatDBTransaction($this->db);

			// Perform all DB actions first. That way we can roll them back
			// if anything goes wrong with the CDN operation.
			if ($this->hasMediaAndEncoding()) {
				$this->media->setOnCdn(false, $this->encoding->shortname);
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
		$disposition = sprintf('attachment; filename="%s"',
			$this->media->getContentDispositionFilename(
				$this->encoding->shortname));

		return array(
			'cache-control'       => 'public, max-age=315360000',
			'content-disposition' => $disposition,
		);
	}

	// }}}
}

?>
