<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/exceptions/SiteNotFoundException.php';

class SiteImageCdnTask extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $id;

	/**
	 * @var string
	 */
	public $operation;

	/**
	 * @var string
	 */
	public $image_path;

	// }}}
	// {{{ protected properties

	/**
	 * @var SiteImage
	 */
	protected $image;

	// }}}
	// {{{ public function execute()

	/**
	 * Executes this task
	 */
	public function execute(SiteCdn $cdn)
	{
		try {
			switch ($this->operation) {
				case 'copy':
					$this->copyImage($cdn);
					break;
				case 'delete':
					$this->deleteImage($cdn);
					break;
			}

			$this->delete();
		} catch (SiteNotFoundException $e) {
			$this->delete();
		} catch (Services_Amazon_S3_Exception $e) {
			$exception = new SwatException($e);
			$exception->process(false);
		}
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table    = 'ImageCDNQueue';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function copyImage()

	/**
	 * Copies this taks's image to a CDN
	 *
	 * @param SiteCdn $cdn the cdn to copy the image to.
	 */
	protected function copyImage(SiteCdn $cdn)
	{
		$image = $this->getImage();
		$shortname = $this->getDimensionShortname();

		$cdn->copyFile(
			$image->getFilePath($shortname),
			$this->image_path,
			$image->getMimeType($shortname));

		$image->setOnCDN(true, $shortname);
	}

	// }}}
	// {{{ protected function deleteImage()

	/**
	 * Deletes this taks's image to a CDN
	 *
	 * @param SiteCdn $cdn the cdn to delete the image from.
	 */
	protected function deleteImage(SiteCdn $cdn)
	{
		try {
			$image = $this->getImage();
			$image->setOnCDN(false, $this->getDimensionShortname());
		} catch (SiteNotFoundException $e) {
		}

		$cdn->deleteFile($this->image_path);
	}

	// }}}
	// {{{ protected function getImage()

	/**
	 * Gets this task's image
	 *
	 * @return SiteImage this task's image.
	 */
	protected function getImage()
	{
		if (!($this->image instanceof SiteImage)) {
			$class_name = SwatDBClassMap::get('SiteImage');

			$image = new $class_name();
			$image->setDatabase($this->db);
			$image->setFileBase('../../www/images');

			if (!($image->load(intval($this->getImageId())))) {
				throw new SiteNotFoundException();
			}

			$this->image = $image;
		}

		return $this->image;
	}

	// }}}
	// {{{ protected function getImageId()

	/**
	 * Gets this task's image id
	 *
	 * @return string this task's image id.
	 */
	protected function getImageId()
	{
		$ruins  = explode('/', $this->image_path);
		$debris = explode('.', $ruins[3]);

		return $debris[0];
	}

	// }}}
	// {{{ protected function getDimensionShortname()

	/**
	 * Gets the shortname of this task's image dimension
	 *
	 * @return string the shortname of this task's image dimension.
	 */
	protected function getDimensionShortname()
	{
		$debris = explode('/', $this->image_path);

		return $debris[2];
	}

	// }}}
}

?>
