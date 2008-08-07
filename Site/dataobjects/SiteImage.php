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

	// }}}
	// {{{ private properties

	private static $image_set_cache = array();
	private $file_base;

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
		);
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		return array_merge(parent::getSerializablePrivateProperties(), array(
			'image_set_shortname',
			'image_set',
			'dimension_bindings',
		));
	}

	// }}}

	// image methods
	// {{{ public function hasDimension()

	public function hasDimension($dimension_shortname)
	{
		$binding = $this->getDimensionBinding($dimension_shortname);
		return ($binding !== null);
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
			$this->getFilename($shortname));
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
			$transaction = new SwatDBTransaction($this->db);
			$this->save(); // save once to set id on this object to use for filenames

			$this->processInternal($image_file);

			$this->save(); // save again to record dimensions
			$transaction->commit();
		} catch (SiteInvalidImageException $e) {
			$transaction->rollback();
			throw $e;
		} catch (SwatException $e) {
			$e->process();
			$transaction->rollback();
		} catch (Exception $e) {
			$e = new SwatException($e);
			$e->process();
			$transaction->rollback();
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
		$this->checkDB();
		$this->image_set = $this->getImageSet();
		$dimension = $this->image_set->getDimensionByShortname($shortname);
		$imagick = new Imagick($image_file);

		try {
			$transaction = new SwatDBTransaction($this->db);
			$this->save(); // save once to set id on this object to use for filenames

			$this->processDimension($imagick, $dimension);
			$this->saveFile($imagick, $dimension);

			$this->save(); // save again to record dimensions
			$transaction->commit();
		} catch (SwatException $e) {
			$e->process();
			$transaction->rollback();
		} catch (Exception $e) {
			$e = new SwatException($e);
			$e->process();
			$transaction->rollback();
		}

		unset($imagick);
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
			$this->saveFile($imagick, $dimension);
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
		if ($dimension->crop)
			$this->cropToDimension($imagick, $dimension);
		else
			$this->fitToDimension($imagick, $dimension);

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

		$imagick->resizeImage($new_width, $new_height,
			Imagick::FILTER_LANCZOS, 1);

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
		}
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
		if ($dimension->max_width !== null &&
			$imagick->getImageWidth() > $dimension->max_width) {

			$new_width = min($dimension->max_width,
				$imagick->getImageWidth());

			$new_height = ceil($imagick->getImageHeight() *
				($new_width / $imagick->getImageWidth()));

			$imagick->resizeImage($new_width, $new_height,
				Imagick::FILTER_LANCZOS, 1);
		}

		if ($dimension->max_height !== null &&
			$imagick->getImageHeight() > $dimension->max_height) {

			$new_height = min($dimension->max_height,
				$imagick->getImageHeight());

			$new_width = ceil($imagick->getImageWidth() *
				($new_height / $imagick->getImageHeight()));

			$imagick->resizeImage($new_width, $new_height,
				Imagick::FILTER_LANCZOS, 1);
		}
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
		$binding->setDatabase($this->db);
		$binding->image      = $this->id;
		$binding->dimension  = $dimension->id;
		$binding->image_type = $dimension->default_type->id;
		$binding->width      = $imagick->getImageWidth();
		$binding->height     = $imagick->getImageHeight();
		$binding->save();

		$this->dimension_bindings->add($binding);
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
		$imagick->setResolution($dimension->dpi, $dimension->dpi);
		$imagick->setCompressionQuality($dimension->quality);

		if ($dimension->interlace)
			$imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE);

		if ($dimension->strip)
			$imagick->stripImage();

		$filename = $this->getFilePath($dimension->shortname);
		$imagick->writeImage($filename);
	}

	// }}}
	// {{{ protected function getImageSet()

	protected function getImageSet()
	{
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
