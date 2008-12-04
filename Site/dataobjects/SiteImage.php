<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatDB/SwatDBDataObject.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'Site/dataobjects/SiteImageSet.php';
require_once 'Site/dataobjects/SiteImageDimensionBindingWrapper.php';
require_once 'Site/exceptions/SiteInvalidImageException.php';

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
	protected $automatically_save = true;

	// }}}
	// {{{ private properties

	private static $image_set_cache = array();
	private $file_base;
	private $crop_boxes = array();
	private $original_dpi;

	// }}}

	// dataobject methods
	// {{{ public function load()

	/**
	 * Loads this object's properties from the database given an id
	 *
	 * @param mixed $id the id of the database row to set this object's
	 *               properties with.
	 *
	 * @return boolean whether data was sucessfully loaded.
	 */
	public function load($id)
	{
		$loaded = parent::load($id);

		if ($loaded && $this->image_set_shortname !== null) {
			if ($this->image_set->shortname != $this->image_set_shortname)
				throw new SwatException('Trying to load image with the '.
					'wrong image set. This may happen if the wrong wrapper '.
					'class is used.');
		}

		if ($loaded) {
			// pre-load dimension bindings. This is useful if the object is
			// serialized before dimensions are loaded.
			$this->setSubDataObject('dimension_bindings',
				$this->loadDimensionBindings());
		}

		return $loaded;
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this image
	 *
	 * @return string the title of this image.
	 */
	public function getTitle()
	{
		return $this->title;
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
	// {{{ protected function deleteInternal()

	/**
	 * Deletes this object from the database
	 */
	protected function deleteInternal()
	{
		$this->image_set = $this->getImageSet();
		$filenames = array();

		foreach ($this->image_set->dimensions as $dimension)
			$filenames[] = $this->getFilePath($dimension->shortname);

		parent::deleteInternal();

		foreach ($filenames as $filename)
			if (file_exists($filename))
				unlink($filename);
	}

	// }}}
	// {{{ protected function getImageDimensionBindingClassName()

	protected function getImageDimensionBindingClassName()
	{
		return SwatDBClassMap::get('SiteImageDimensionBinding');
	}

	// }}}
	// {{{ protected function getImageDimensionBindingWrapperClassName()

	protected function getImageDimensionBindingWrapperClassName()
	{
		return SwatDBClassMap::get('SiteImageDimensionBindingWrapper');
	}

	// }}}
	// {{{ protected function getSerializableSubDataObjects()

	protected function getSerializableSubDataObjects()
	{
		return array(
			'image_set',
			'dimension_bindings',
		);
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		return array_merge(parent::getSerializablePrivateProperties(), array(
			'image_set_shortname',
		));
	}

	// }}}

	// image methods
	// {{{ public function hasDimension()

	public function hasDimension($dimension_shortname)
	{
		$found = false;

		if ($this->image_set->hasDimension($dimension_shortname)) {
			$binding = $this->getDimensionBinding($dimension_shortname);
			$found = ($binding !== null);
		}

		return $found;
	}

	// }}}
	// {{{ public function getWidth()

	public function getWidth($dimension_shortname)
	{
		$binding = $this->getDimensionBinding($dimension_shortname);

		if ($binding === null)
			throw new SwatException(sprintf(
				'Image dimension “%s” does not exist.',	$dimension_shortname));

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
	// {{{ public function getFilesize()

	public function getFilesize($dimension_shortname)
	{
		$binding = $this->getDimensionBinding($dimension_shortname);
		return $binding->filesize;
	}

	// }}}
	// {{{ public function getFilename()


	public function getFilename($shortname)
	{
		// get extension if it exists, otherwise get the default from dimension
		$binding = $this->getDimensionBinding($shortname);
		if ($binding === null) {
			$dimension = $this->image_set->getDimensionByShortname($shortname);
			$extension = $dimension->default_type->extension;
		} else {
			$extension = $binding->image_type->extension;
		}

		if ($this->image_set->obfuscate_filename) {
			$filename = $this->filename;
		} else {
			$filename = $this->id;
		}

		return sprintf('%s.%s', $filename, $extension);
	}

	// }}}
	// {{{ public function getDpi()

	public function getDpi($dimension_shortname)
	{
		$binding = $this->getDimensionBinding($dimension_shortname);
		return $binding->dpi;
	}

	// }}}
	// {{{ public function getMimeType()


	public function getMimeType($dimension_shortname)
	{
		$binding = $this->getDimensionBinding($dimension_shortname);
		return $binding->image_type->mime_type;
	}

	// }}}
	// {{{ public function getUri()

	public function getUri($shortname, $prefix = null)
	{
		$dimension = $this->image_set->getDimensionByShortname(
			$shortname);

		$uri = sprintf('%s/%s/%s',
			$this->image_set->shortname,
			$dimension->shortname,
			$this->getFilename($shortname));

		if ($this->getUriBase() !== null)
			$uri = $this->getUriBase().'/'.$uri;

		if ($prefix !== null && !strpos($uri, '://'))
			$uri = $prefix.$uri;

		return $uri;
	}

	// }}}
	// {{{ public function getFilePath()

	/**
	 * Gets the full file path of a dimension
	 *
	 * This includes the directory and the filename.
	 *
	 * @return string the full file path of a dimension.
	 */
	public function getFilePath($shortname)
	{
		$directory = $this->getFileDirectory($shortname);
		$filename  = $this->getFilename($shortname);
		return $directory.DIRECTORY_SEPARATOR.$filename;
	}

	// }}}
	// {{{ public function getFileDirectory()

	/**
	 * Gets the directory of a dimension
	 *
	 * @return string the directory of a dimension.
	 */
	public function getFileDirectory($shortname)
	{
		$dimension = $this->image_set->getDimensionByShortname(
			$shortname);

		return $this->getFileBase().DIRECTORY_SEPARATOR.
			$this->image_set->shortname.DIRECTORY_SEPARATOR.
			$dimension->shortname;
	}

	// }}}
	// {{{ public function getImgTag()

	public function getImgTag($shortname, $prefix = null)
	{
		// don't use getImageSet() here as this should always be loaded with
		// the database value for image_set, and instance does not have to be
		// specified to display an image.
		$dimension = $this->image_set->getDimensionByShortname($shortname);

		$img_tag = new SwatHtmlTag('img');
		$img_tag->src = $this->getUri($shortname, $prefix);

		$img_tag->width = $this->getWidth($shortname);
		$img_tag->height = $this->getHeight($shortname);

		$title = $this->getTitle();
		if ($title !== null) {
			$img_tag->alt = sprintf(Site::_('Image of %s'), $title);
			$img_tag->title = $title;
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

		foreach ($this->dimension_bindings as $binding) {
			$id = ($binding->dimension instanceof SiteImageDimension) ?
				$binding->dimension->id : $binding->dimension;

			if ($dimension->id === $id)
				return $binding;
		}

		return null;
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

		$wrapper =$this->getImageDimensionBindingWrapperClassName();
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
	 * @param string $image_file the image file to process
	 *
	 * @throws SwatException if the image can't be processed.
	 */
	public function process($image_file)
	{
		if ($this->automatically_save)
			$this->checkDB();

		$this->image_set = $this->getImageSet();

		if ($this->original_filename === null) {
			// extra space is to overcome a UTF-8 problem with basename
			$this->original_filename = ltrim(basename(' '.$image_file));
		}

		$wrapper = $this->getImageDimensionBindingWrapperClassName();
		$this->dimension_bindings = new $wrapper();

		if ($this->image_set->obfuscate_filename)
			$this->filename = sha1(uniqid(rand(), true));

		if (!extension_loaded('imagick') || !class_exists('Imagick'))
			throw new SwatException(
				'Class Imagick from extension imagick > 2.0.0 not found.');

		try {
			if ($this->automatically_save) {
				$transaction = new SwatDBTransaction($this->db);
				$this->save(); // save once to set id on this object to use for filenames
			}

			$this->processInternal($image_file);

			if ($this->automatically_save) {
				$this->save(); // save again to record dimensions
				$transaction->commit();
			}
		} catch (SiteInvalidImageException $e) {
			if ($this->automatically_save) {
				$transaction->rollback();
			}
			throw $e;
		} catch (SwatException $e) {
			if ($this->automatically_save) {
				$transaction->rollback();
			}
			$e->process();
		} catch (Exception $e) {
			if ($this->automatically_save) {
				$transaction->rollback();
			}
			$e = new SwatException($e);
			$e->process();
		}

	}

	// }}}
	// {{{ public function processManual()

	/**
	 * Manually process one dimension of an image
	 *
	 * @param string $image_file the image file to process
	 * @param string $shortname the shortname of the dimension to process
	 */
	public function processManual($image_file, $shortname)
	{
		if ($this->automatically_save)
			$this->checkDB();

		$this->image_set = $this->getImageSet();
		$dimension = $this->image_set->getDimensionByShortname($shortname);
		$imagick = new Imagick($image_file);

		try {
			if ($this->automatically_save) {
				$transaction = new SwatDBTransaction($this->db);
				$this->save(); // save once to set id on this object to use for filenames
			}

			$this->processDimension($imagick, $dimension);
			$this->saveFile($imagick, $dimension);

			if ($this->automatically_save) {
				$this->save(); // save again to record dimensions
				$transaction->commit();
			}
		} catch (SwatException $e) {
			$e->process();

			if ($this->automatically_save)
				$transaction->rollback();
		} catch (Exception $e) {
			$e = new SwatException($e);
			$e->process();

			if ($this->automatically_save)
				$transaction->rollback();
		}

		unset($imagick);
	}

	// }}}
	// {{{ public function setDpi()

	/**
	 * Specify the DPI of the image being processed
	 *
	 * @param integer $dpi The dpi of the image being processed
	 */
	public function setDpi($dpi)
	{
		$this->original_dpi = $dpi;
	}

	// }}}
	// {{{ public function setDimensionCropBox()

	/**
	 * Specify a crop bounding-box for a dimension
	 *
	 * The dimensions and positions for the crop box should be at the scale of
	 * the source image. If a crop box exists for a dimension, the image will
	 * first be cropped to the specified coordinates and then default resizing
	 * as specified for the dimension will be applied.
	 *
	 * @param string $shortname the shortname of the dimension
	 * @param integer $crop_width Width of the crop bounding box
	 * @param integer $crop_height Height of the crop bounding box
	 * @param integer $crop_top Position of the top side of the crop bounding
	 *                box
	 * @param integer $crop_left position of the left side of the crop bounding
	 *                box
	 */
	public function setDimensionCropBox($shortname, $crop_width, $crop_height,
		$crop_top, $crop_left)
	{
		$this->crop_boxes[$shortname] = array($crop_width, $crop_height,
			$crop_top, $crop_left);
	}

	// }}}
	// {{{ protected function processInternal()

	/**
	 * Processes the image
	 *
	 * At this point in the process, the image already has a filename and id
	 * and is wrapped in a database transaction.
	 *
	 * @param string $image_file the image file to process
	 */
	protected function processInternal($image_file)
	{
		foreach ($this->image_set->dimensions as $dimension) {
			try {
				$imagick = new Imagick($image_file);
			} catch (ImagickException $e) {
				throw new SiteInvalidImageException();
			}

			$this->processDimension($imagick, $dimension);

			if ($dimension->max_width === null &&
				$dimension->max_height === null &&
				$dimension->default_type === null) {

				$this->copyFile($image_file, $dimension);
			} else {
				$this->saveFile($imagick, $dimension);
			}

			unset($imagick);
		}
	}

	// }}}
	// {{{ protected function processDimension()

	/**
	 * Resizes an image for the given dimension
	 *
	 * @param Imagick $imagick the imagick instance to work with.
	 * @param SiteImageDimension $dimension the dimension to process.
	 */
	protected function processDimension(Imagick $imagick,
		SiteImageDimension $dimension)
	{
		if (isset($this->crop_boxes[$dimension->shortname])) {
			$this->cropToBox($imagick, $dimension,
				$this->crop_boxes[$dimension->shortname]);
		}

		if ($dimension->crop) {
			$this->cropToDimension($imagick, $dimension);
		} else {
			$this->fitToDimension($imagick, $dimension);
		}

		$this->saveDimensionBinding($imagick, $dimension);
	}

	// }}}
	// {{{ protected function cropToDimension()

	/**
	 * Resizes and crops an image to a given dimension
	 *
	 * @param Imagick $imagick the imagick instance to work with.
	 * @param SiteImageDimension $dimension the dimension to process.
	 */
	protected function cropToDimension(Imagick $imagick,
		SiteImageDimension $dimension)
	{
		$height = $dimension->max_height;
		$width = $dimension->max_width;

		if ($imagick->getImageWidth() === $dimension->max_width &&
			$imagick->getImageHeight() === $dimension->max_height)
				return;

		if ($imagick->getImageWidth() / $width >
			$imagick->getImageHeight() / $height) {

			$new_height = $height;
			$new_width = ceil($imagick->getImageWidth() *
				($new_height / $imagick->getImageHeight()));

		} else {
			$new_width = $width;
			$new_height = ceil($imagick->getImageHeight() *
				($new_width / $imagick->getImageWidth()));
		}

		$this->setDimensionDpi($imagick, $dimension,
			$imagick->getImageWidth(), $new_width);

		$imagick->resizeImage($new_width, $new_height,
			Imagick::FILTER_LANCZOS, 1);

		// Set page geometry to the new size so subsequent crops will use
		// will use the geometry of the new image instead of the original
		// image.
		$imagick->setImagePage($new_width, $new_height, 0, 0);

		// crop to fit
		if ($imagick->getImageWidth() != $width ||
			$imagick->getImageHeight() != $height) {

			$offset_x = 0;
			$offset_y = 0;

			if ($imagick->getImageWidth() > $width)
				$offset_x = ceil(($imagick->getImageWidth() - $width) / 2);

			if ($imagick->getImageHeight() > $height)
				$offset_y = ceil(($imagick->getImageHeight() - $height) / 2);

			$imagick->cropImage($width, $height, $offset_x, $offset_y);

			// Set page geometry to the newly cropped size so subsequent crops
			// will use the geometry of the new image instead of the original
			// image.
			$imagick->setImagePage($width, $height, $offset_x, $offset_y);
		}
	}

	// }}}
	// {{{ protected function cropToBox()

	/**
	 * Resizes and crops an image to a given crop bounding box
	 *
	 * @param Imagick $imagick the imagick instance to work with.
	 * @param SiteImageDimension $dimension the dimension to process.
	 * @param array $bounding_box the dimension to process.
	 */
	protected function cropToBox(Imagick $imagick,
		SiteImageDimension $dimension, array $bounding_box)
	{
		$width = $bounding_box[0];
		$height = $bounding_box[1];
		$offset_x = $bounding_box[2];
		$offset_y = $bounding_box[3];

		$imagick->cropImage($width, $height, $offset_x, $offset_y);

		// Set page geometry to the newly cropped size so subsequent crops
		// will use the geometry of the new image instead of the original
		// image.
		$imagick->setImagePage($width, $height, $offset_x, $offset_y);
	}

	// }}}
	// {{{ protected function fitToDimension()

	/**
	 * Resizes an image to fit in a given dimension
	 *
	 * @param Imagick $imagick the imagick instance to work with.
	 * @param SiteImageDimension $dimension the dimension to process.
	 */
	protected function fitToDimension(Imagick $imagick,
		SiteImageDimension $dimension)
	{
		$this->setDimensionDpi($imagick, $dimension);

		if ($dimension->max_width !== null &&
			$imagick->getImageWidth() > $dimension->max_width) {

			$new_width = min($dimension->max_width,
				$imagick->getImageWidth());

			$new_height = ceil($imagick->getImageHeight() *
				($new_width / $imagick->getImageWidth()));

			$this->setDimensionDpi($imagick, $dimension,
				$imagick->getImageWidth(), $new_width);

			$imagick->resizeImage($new_width, $new_height,
				Imagick::FILTER_LANCZOS, 1);

			// Set page geometry to the new size so subsequent crops will use
			// will use the geometry of the new image instead of the original
			// image.
			$imagick->setImagePage($new_width, $new_height, 0, 0);
		}

		if ($dimension->max_height !== null &&
			$imagick->getImageHeight() > $dimension->max_height) {

			$new_height = min($dimension->max_height,
				$imagick->getImageHeight());

			$new_width = ceil($imagick->getImageWidth() *
				($new_height / $imagick->getImageHeight()));

			$this->setDimensionDpi($imagick, $dimension,
				$imagick->getimagewidth(), $new_width);

			$imagick->resizeImage($new_width, $new_height,
				Imagick::FILTER_LANCZOS, 1);

			// Set page geometry to the new size so subsequent crops will use
			// will use the geometry of the new image instead of the original
			// image.
			$imagick->setImagePage($new_width, $new_height, 0, 0);
		}
	}

	// }}}
	// {{{ protected function setDimensionDpi()

	protected function setDimensionDpi(Imagick $imagick,
		SiteImageDimension $dimension, $original_width = 1, $resized_width = 1)
	{
		$dpi = ($this->original_dpi === null) ? $dimension->dpi :
			round($this->original_dpi / ($original_width / $resized_width));

		$imagick->setImageResolution($dpi, $dpi);
	}

	// }}}
	// {{{ protected function saveDimensionBinding()

	/**
	 * Saves an image dimension binding
	 *
	 * @param Imagick $imagick the imagick instance to work with.
	 * @param SiteImageDimension $dimension the image's dimension.
	 */
	protected function saveDimensionBinding(Imagick $imagick,
		SiteImageDimension $dimension)
	{
		$class_name = $this->getImageDimensionBindingClassName();
		$binding = new $class_name();
		$binding->image      = $this->id;
		$binding->dimension  = $dimension->id;
		$binding->image_type =
			$this->getDimensionImageType($imagick, $dimension);

		$binding->width      = $imagick->getImageWidth();
		$binding->height     = $imagick->getImageHeight();
		$binding->filesize   = $imagick->getImageSize();

		$resolution = $imagick->getImageResolution();
		$binding->dpi  = intval($resolution['x']);

		if ($this->automatically_save) {
			$binding->setDatabase($this->db);
			$binding->save();
		}

		$this->dimension_bindings->add($binding);
	}

	// }}}
	// {{{ protected function getDimensionImageType()

	/**
	 * Gets the image type for a dimension. If default image type is specified,
	 * the image is converted to that type, otherwise the type of the image is
	 * preserved.
	 *
	 * @param Imagick $imagick the imagick instance to work with.
	 * @param SiteImageDimension $dimension the image's dimension.
	 *
	 * @return SiteImageType The type of image for the dimension
	 */
	protected function getDimensionImageType(Imagick $imagick,
		SiteImageDimension $dimension)
	{
		if ($dimension->default_type === null) {
			$class_name = SwatDBClassMap::get('SiteImageType');
			$image_type = new $class_name();
			$image_type->setDatabase($this->db);
			$mime_type = 'image/'.$imagick->getImageFormat();
			$found = $image_type->loadByMimeType($mime_type);
			if (!$found) {
				throw new SiteInvalidImageException(sprintf(
					'The mime-type “%s” is not present in the ImageType '.
					'table.',
					$mime_type));
			}

			$type = $image_type;
		} else {
			$type = $dimension->default_type;
		}

		return $type;
	}

	// }}}
	// {{{ protected function saveFile()

	/**
	 * Saves the current image
	 *
	 * @param Imagick $imagick the imagick instance to work with.
	 * @param SiteImageDimension $dimension the dimension to save.
	 */
	protected function saveFile(Imagick $imagick,
		SiteImageDimension $dimension)
	{
		$imagick->setCompressionQuality($dimension->quality);

		if ($dimension->interlace)
			$imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE);

		if ($dimension->strip)
			$imagick->stripImage();

		// recursively create file directories
		$directory = $this->getFileDirectory($dimension->shortname);
		if (!file_exists($directory)) {
			mkdir($directory, 0777, true);
		}

		$filename = $this->getFilePath($dimension->shortname);
		$imagick->writeImage($filename);
	}

	// }}}
	// {{{ protected function copyFile()

	/**
	 * Copies the image
	 *
	 * @param string $image_file the image file to save
	 * @param SiteImageDimension $dimension the dimension to save.
	 */
	protected function copyFile($image_file, SiteImageDimension $dimension)
	{
		// recursively create file directories
		$directory = $this->getFileDirectory($dimension->shortname);
		if (!file_exists($directory)) {
			mkdir($directory, 0777, true);
		}

		$filename = $this->getFilePath($dimension->shortname);
		copy($image_file, $filename);
	}

	// }}}
	// {{{ protected function getImageSet()

	protected function getImageSet()
	{
		if ($this->image_set instanceof SiteImageSet)
			return $this->image_set;

		if ($this->image_set_shortname === null)
			throw new SwatException('To process images, an image type '.
				'shortname must be defined in the image dataobject.');

		$class_name = SwatDBClassMap::get('SiteImageSet');
		$image_set = new $class_name();
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
