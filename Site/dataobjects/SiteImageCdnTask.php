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
 * @copyright 2010-2011 silverorange
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
			$attempt = sprintf(
				Site::_('Copying the dimension ‘%s’ of image ‘%s’ ... '),
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
	// {{{ protected function copyItem()

	protected function copyItem(SiteCdnModule $cdn)
	{
		try {
			$transaction = new SwatDBTransaction($this->db);

			// Perform all DB actions first. That way we can roll them back
			// if anything goes wrong with the CDN operation.
			if ($this->hasImageAndDimension()) {
				$this->image->setOnCdn(true, $this->dimension->shortname);
			}

			$this->delete();

			if ($this->hasImageAndDimension()) {
				$cdn->copyFile(
					$this->image->getFilePath($this->dimension->shortname),
					$this->image->getUriSuffix($this->dimension->shortname),
					$this->image->getMimeType($this->dimension->shortname),
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
			if ($this->hasImageAndDimension()) {
				$this->image->setOnCdn(false, $this->dimension->shortname);
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
