<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatDB/SwatDBDataObject.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'Site/dataobjects/SiteImageSet.php';
require_once 'Image/Transform.php';

/**
 * An image data object
 *
 * @package   Site
 * @copyright 2008 silverorange
 */
class SiteImage extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Filename
	 *
	 * Only used if the ImageSet for this image sets obfuscate_filename = true.
	 *
	 * @var string
	 */
	public $filename;

	/**
	 * Original filename
	 *
	 * The original name of the file before it was processed.
	 *
	 * @var string
	 */
	public $original_filename;

	/**
	 * Title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Description
	 *
	 * @var string
	 */
	public $description;

	// }}}
	// {{{ protected properties

	/**
	 * Image base path
	 *
	 * @var string
	 */
	protected $path = 'images';

	// }}}
	// {{{ private properties

	/**
	 * Cached image sets
	 *
	 * @var array
	 */
	private $sets = array();

	/**
	 * Cached image dimensions
	 *
	 * @var array
	 */
	private $dimensions = array();

	// }}}

	// {{{ public function getURI()

	public function getURI($dimension_shortname)
	{
		$dimension = new SiteImageDimension();
		$dimension->setDatabase($this->db);
		$dimension->loadByShortname($dimension_shortname);

		return sprintf('%s/%s/%s/%s.jpg',
			$this->path,
			$dimension->image_set->shortname,
			$dimension->shortname,
			$this->getFilename());
	}

	// }}}
	// {{{ public function getImgTag()

	public function getImgTag($dimension_shortname)
	{
		$dimension = $this->getDimensionByShortname($dimension_shortname);

		$img_tag = new SwatHtmlTag('img');
		$img_tag->src = $this->getURI($dimension_shortname);

		// TODO: get dimensions nicely
		$img_tag->width = $this->width;
		$img_tag->height = $this->height;

		if ($this->title !== null) {
			$img_tag->alt = sprintf(Site::_('Image of %'),
				$this->title);
			$img_tag->title = $this->title;
		} else {
			$img_tag->alt = '';
		}

		return $img_tag;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('image_set',
			SwatDBClassMap::get('SiteImageSet'));

		$this->table = 'Image';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ private function getFilename()

	private function getFilename()
	{
		if ($this->image_set->obfuscate_filename) {
			return $this->filename;
		} else {
			return $this->id;
		}
	}

	// }}}

	// processing methods
	// {{{ public function process()

	/**
	 * Does resizing for images
	 *
	 * The image is resized for each dimension. The dataobject is automatically
	 * saved after the image has been processed.
	 *
	 * @param Image_Transform $image_file the image file to process
	 *
	 * @return boolean true on successful processing of all dimensions, false
	 *                 if an error has occurred.
	 *
	 * @throws SwatException if the image can't be processed.
	 */
	public function process($image_file)
	{
		$this->checkDB();

		// extra space is to overcome a UTF-8 problem with basename
		$this->original_filename = ltrim(basename(' '.$image_file));

		if ($this->image_set->obfuscate_filename)
			$this->filename = sha1(uniqid(rand(), true));

		$image = Image_Transform::factory('Imagick2');

		if ($image->image === null)
			throw new SwatException(sprintf('Image “%s” can not be loaded.',
				$image_file));

		try {
			$transaction = new SwatDBTransaction($this->db);
			$this->save(); // save once to set id on this object to use for filenames

			foreach ($this->image_set->dimensions as $dimension) {
				$image->load($image_file);
				$this->processDimension($image, $dimension);
			}

			$this->save(); // save again to record dimensions
			$transaction->commit();
		} catch (SwatException $e) {
			$e->process();
			$transaction->rollback();
		}

	}

	// }}}
	// {{{ protected function processDimension()

	/**
	 * Resizes an image for the given dimension
	 *
	 * @param Image_Transform $image the image transformer to work with.
	 * @param SiteImageDimension $dimension the dimension to process.
	 */
	protected function processDimension(Image_Transform $image,
		SiteImageDimension $dimension)
	{
		$this->resizeDimension($image, $dimension);

		$sql = sprintf('insert into ImageDimensionBinding
			(image, dimension, width, height)
			values (%s, %s, %s, %s)',
			$this->db->quote($this->id, 'integer'),
			$this->db->quote($dimension->id, 'integer'),
			$this->db->quote($image->img_x, 'integer'),
			$this->db->quote($image->img_y, 'integer'));
		SwatDB::query($this->db, $sql);

		$this->saveDimension($image, $dimension);
	}

	// }}}
	// {{{ protected function resizeDimension()

	/**
	 * Resizes an image for the given dimension
	 *
	 * @param Image_Transform $image the image transformer to work with.
	 * @param SiteImageDimension $dimension the dimension to process.
	 */
	protected function resizeDimension(Image_Transform $image,
		SiteImageDimension $dimension)
	{
		if ($dimension->crop) {
			$height = $dimension->max_height;
			$width = $dimension->max_width;

			if ($image->img_x / $width > $image->img_y / $height) {
				$new_y = $height;
				$new_x = ceil(($new_y / $image->img_y) * $image->img_x);
			} else {
				$new_x = $width;
				$new_y = ceil(($new_x / $image->img_x) * $image->img_y);
			}

			$image->resize($new_x, $new_y);

			// crop to fit
			if ($image->new_x != $width || $image->new_y != $height) {
				$offset_x = 0;
				$offset_y = 0;

				if ($image->new_x > $width)
					$offset_x = ceil(($image->new_x - $width) / 2);

				if ($image->new_y > $height)
					$offset_y = ceil(($image->new_y - $height) / 2);

				$image->crop($width, $height, $offset_x, $offset_y);
			}
		} else {
			if ($dimension->max_width !== null)
				$image->fitX($dimension->max_width);

			if ($dimension->max_height !== null)
				$image->fitY($dimension->max_height);
		}
	}

	// }}}
	// {{{ protected function saveDimension()

	/**
	 * Saves the current image
	 *
	 * @param Image_Transform $image the image transformer to work with.
	 * @param SiteImageDimension $dimension the dimension to save.
	 */
	protected function saveDimension(Image_Transform $image,
		SiteImageDimension $dimension)
	{
		$image->setDpi($dimension->dpi,	$dimension->dpi);
		$image->strip();

		// TODO: need a way to reference the www dir
		$file = sprintf('../%s', $this->getURI($dimension->shortname));
		$image->save($file, false, $dimension->quality);
	}

	// }}}
}

?>
