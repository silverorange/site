<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteImageType.php';

/**
 * An image dimension binding data object
 *
 * @package   Site
 * @copyright 2008 silverorange
 */
class SiteImageDimensionBinding extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Width
	 *
	 * @var integer
	 */
	public $width;

	/**
	 * Height
	 *
	 * @var integer
	 */
	public $height;

	/**
	 * File size in bytes
	 *
	 * @var integer
	 */
	public $filesize;

	/**
	 * Dpi
	 *
	 * @var integer
	 */
	public $dpi;

	/**
	 * Whether or not this dimension is on a CDN
	 *
	 * @var boolean
	 */
	public $on_cdn;

	/**
	 * Dimension Id
	 *
	 * This is not an internal property since alternative effiecient methods
	 * are used to load dimensions and dimension bindings.
	 *
	 * @var integer
	 */
	public $dimension;

	/**
	 * Image Id
	 *
	 * This is not an internal property since alternative effiecient methods
	 * are used to load dimensions and dimension bindings.
	 *
	 * @var integer
	 */
	public $image;

	// }}}
	// {{{ protected properties

	/**
	 * Image field name
	 *
	 * @var string
	 */
	protected $image_field = 'image';

	// }}}
	// {{{ private properties

	private static $image_type_cache = array();

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'ImageDimensionBinding';

		$this->registerInternalProperty('image_type',
			SwatDBClassMap::get('SiteImageType'));
	}

	// }}}
	// {{{ protected function hasSubDataObject()

	protected function hasSubDataObject($key)
	{
		$found = parent::hasSubDataObject($key);

		if ($key === 'image_type' && !$found) {
			$image_type_id = $this->getInternalValue('image_type');

			if ($image_type_id !== null &&
				array_key_exists($image_type_id, self::$image_type_cache)) {
				$this->setSubDataObject('image_type',
					self::$image_type_cache[$image_type_id]);

				$found = true;
			}
		}

		return $found;
	}

	// }}}
	// {{{ protected function setSubDataObject()

	protected function setSubDataObject($name, $value)
	{
		if ($name === 'image_type')
			self::$image_type_cache[$value->id] = $value;

		parent::setSubDataObject($name, $value);
	}

	// }}}
	// {{{ protected function saveInternal()

	/**
	 * Saves this object to the database
	 *
	 * Only modified properties are updated.
	 */
	protected function saveInternal()
	{
		$sql = sprintf('delete from %s where dimension = %s and %s = %s',
			$this->table,
			$this->db->quote($this->dimension, 'integer'),
			$this->image_field,
			$this->db->quote($this->image, 'integer'));

		SwatDB::exec($this->db, $sql);

		parent::saveInternal();
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		return array_merge(parent::getSerializablePrivateProperties(), array(
			'image_type',
		));
	}

	// }}}
}

?>
