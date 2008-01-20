<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatDB/SwatDBDataObject.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'Site/dataobjects/SiteImageSet.php';
require_once 'Site/dataobjects/SiteImageDimensionBindingWrapper.php';
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

	protected $image_set_shortname;

	// }}}
	// {{{ private properties

	private static $image_set_cache = array();
	private $file_base;

	// }}}

	// dataobject methods
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('image_set',
			SwatDBClassMap::get('SiteImageSet'));

		$this->table = 'Image';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function hasSubDataObject()

	protected function hasSubDataObject($key)
	{
		$found = parent::hasSubDataObject($key);

		if ($key === 'image_set' && !$found) {
			$image_set_id = $this->getInternalValue('image_set');

			if ($image_set_id !== null &&
				array_key_exists($image_set_id, self::$image_set_cache)) {
				$this->setSubDataObject('image_set',
					self::$image_set_cache[$image_set_id]);

				$found = true;
			}
		}

		return $found;
	}

	// }}}
	// {{{ protected function setSubDataObject()

	protected function setSubDataObject($name, $value)
	{
		if ($name === 'image_set')
			self::$image_set_cache[$value->id] = $value;

		parent::setSubDataObject($name, $value);
	}

	// }}}

	// image methods
	// {{{ public function getWidth()

	public function getWidth($dimension_shortname)
	{
		$binding = $this->getDimensionBinding($dimension_shortname);
		return $binding->width;
	}

	// }}}
	// {{{ public function getHeight()

	public function getHeight($dimension_shortname)
	{
		$binding = $this->getDimensionBinding($dimension_shortname);
		return $binding->height;
	}

	// }}}
	// {{{ public function getFilename()


	public function getFilename(SiteImageDimension $dimension = null)
	{
		// get extension if it exists, otherwise get the default from dimension
		$binding = $this->getDimensionBinding($dimension->shortname);
		$extension = ($binding === null) ?
			$dimension->default_type->extension :
				$binding->image_type->extension;

		if ($this->image_set->obfuscate_filename) {
			$filename = $this->filename;
		} else {
			$filename = $this->id;
		}

		return sprintf('%s.%s', $filename, $extension);
	}

	// }}}
	// {{{ public function getUri()

	public function getUri($shortname, $prefix = null)
	{
		$dimension = $this->image_set->getDimensionByShortname(
			$shortname);

		$uri = sprintf('%s/%s/%s/%s',
			$this->getUriBase(),
			$this->image_set->shortname,
			$dimension->shortname,
			$this->getFilename($dimension));

		if ($prefix !== null)
			$uri = $prefix.$uri;

		return $uri;
	}

	// }}}
	// {{{ public function getFilePath()

	public function getFilePath($shortname)
	{
		$dimension = $this->image_set->getDimensionByShortname(
			$shortname);

		return sprintf('%s/%s/%s/%s',
			$this->getFileBase(),
			$this->image_set->shortname,
			$dimension->shortname,
			$this->getFilename($dimension));
	}

	// }}}
	// {{{ public function getImgTag()

	public function getImgTag($shortname, $prefix = null)
	{
		$dimension = $this->image_set->getDimensionByShortname($shortname);

		$img_tag = new SwatHtmlTag('img');
		$img_tag->src = $this->getUri($shortname, $prefix);

		$img_tag->width = $this->getWidth($shortname);
		$img_tag->height = $this->getHeight($shortname);

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
	// {{{ public function setFileBase()

	public function setFileBase($path)
	{
		$this->file_base = $path;
	}

	// }}}
	// {{{ protected function getUriBase()

	protected function getUriBase()
	{
		return 'images';
	}

	// }}}
	// {{{ protected function getFileBase()

	protected function getFileBase()
	{
		if ($this->file_base === null)
			throw new SwatException('File base has not been set on the '.
				'dataobject. Set the path to the webroot using '.
				'setFileBase().');

		return $this->file_base;
	}

	// }}}
	// {{{ private function getDimensionBinding()

	private function getDimensionBinding($dimension_shortname)
	{
		$dimension = $this->image_set->getDimensionByShortname(
			$dimension_shortname);

		return $this->dimension_bindings->getByIndex($dimension->id);
	}

	// }}}

	// loader methods
	// {{{ protected function loadDimensionBindings()

	/**
	 * Loads the dimension bindings for this image
	 *
	 * @return SiteImageDimensionBindingWrapper a recordset of dimension
	 *                                           bindings.
	 */
	protected function loadDimensionBindings()
	{
		$sql = 'select * from ImageDimensionBinding
				where ImageDimensionBinding.image = %s';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'));

		$wrapper = SwatDBClassMap::get('SiteImageDimensionBindingWrapper');
		return SwatDB::query($this->db, $sql, $wrapper);
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

		$this->image_set = $this->getImageSet();

		// extra space is to overcome a UTF-8 problem with basename
		$this->original_filename = ltrim(basename(' '.$image_file));
		$this->dimension_bindings = new SiteImageDimensionBindingWrapper();

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
		$this->saveDimensionBinding($image, $dimension);
		$this->saveFile($image, $dimension);
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
	// {{{ protected function saveDimensionBinding()

	/**
	 * Saves an image dimension binding
	 *
	 * @param Image_Transform $image the image being resized.
	 * @param SiteImageDimension $dimension the image's dimension.
	 */
	protected function saveDimensionBinding(Image_Transform $image,
		SiteImageDimension $dimension)
	{
		$binding = new SiteImageDimensionBinding();
		$binding->setDatabase($this->db);
		$binding->image = $this->id;
		$binding->dimension = $dimension->id;
		$binding->image_type = $dimension->default_type->id;
		$binding->width = $image->new_x;
		$binding->height = $image->new_y;
		$binding->save();

		$this->dimension_bindings->add($binding);
	}

	// }}}
	// {{{ protected function saveFile()

	/**
	 * Saves the current image
	 *
	 * @param Image_Transform $image the image transformer to work with.
	 * @param SiteImageDimension $dimension the dimension to save.
	 */
	protected function saveFile(Image_Transform $image,
		SiteImageDimension $dimension)
	{
		$image->setDpi($dimension->dpi,	$dimension->dpi);
		$image->strip();

		$file = $this->getFilePath($dimension->shortname);
		$image->save($file, false, $dimension->quality);

		// TODO: throw exception if file can't be saved
	}

	// }}}
	// {{{ private function setImageSet()

	private function getImageSet()
	{
		if ($this->image_set_shortname === null)
			throw new SwatException('To process images, an image type '.
				'shortname must be defined in the image dataobject.');

		$image_set = new SiteImageSet();
		$image_set->setDatabase($this->db);
		$found = $image_set->loadByShortname($this->image_set_shortname);

		if (!$found)
			throw new SwatException(sprintf('Image set “%s” does not exist.',
				$this->image_set_shortname));

		return $image_set;
	}

	// }}}
}

?>
