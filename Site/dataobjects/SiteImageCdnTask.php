<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'Site/dataobjects/SiteCdnTask.php';
require_once 'Site/dataobjects/SiteImage.php';
require_once 'Site/dataobjects/SiteImageDimension.php';

/**
 * A task that should be performed on a CDN in the near future
 *
 * @package   Site
 * @copyright 2010-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteImageCdnTask extends SiteCdnTask
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
			$this->copyImage($cdn);
			break;
		case 'delete':
			$this->deleteImage($cdn);
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
			$attempt = sprintf(Site::_('Copying image %s, dimension %s ... '),
				$this->image->id,
				$this->dimension->id);

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

		$this->registerInternalProperty('image',
			SwatDBClassMap::get('SiteImage'));

		$this->registerInternalProperty('dimension',
			SwatDBClassMap::get('SiteImageDimension'));

		$this->table = 'ImageCdnQueue';
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
	// {{{ protected function copyImage()

	protected function copyImage(SiteCdnModule $cdn)
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
	// {{{ protected function deleteImage()

	protected function deleteImage(SiteCdnModule $cdn)
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
	// {{{ protected function getHttpHeaders()

	protected function getHttpHeaders()
	{
		return array(
			'cache-control' => 'public, max-age=315360000',
		);
	}

	// }}}
}

?>
