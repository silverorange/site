<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteImageSet.php';
require_once 'Site/dataobjects/SiteImageType.php';

/**
 * An image dimension data object
 *
 * @package   Site
 * @copyright 2008 silverorange
 */
class SiteImageDimension extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Short, textual identifer for this dimension
	 *
	 * The shortname must be unique within this dimensions' set.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * Title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Maximum width in pixels
	 *
	 * @var integer
	 */
	public $max_width;

	/**
	 * Maximum height in pixels
	 *
	 * @var integer
	 */
	public $max_height;

	/**
	 * Crop
	 *
	 * @var boolean
	 */
	public $crop;

	/**
	 * DPI
	 *
	 * @var integer
	 */
	public $dpi;

	/**
	 * Quality
	 *
	 * @var integer
	 */
	public $quality;

	/**
	 * Strip embedded image data?
	 *
	 * @var boolean
	 */
	public $strip;

	/**
	 * Save interlaced (progressive)
	 *
	 * @var boolean
	 */
	public $interlace;

	// }}}
	// {{{ private properties

	private static $default_type_cache = array();

	// }}}
	// {{{ public function loadByShortname()

	/**
	 * Loads a dimension from the database with a shortname
	 *
	 * @param string $set_shortname the shortname of the set
	 * @param string $dimension_shortname the shortname of the dimension
	 *
	 * @return boolean true if a dimension was successfully loaded and false if
	 *                  no dimension was found at the specified shortname.
	 */
	public function loadByShortname($set_shortname, $dimension_shortname)
	{
		$this->checkDB();

		$found = false;

		$sql = 'select * from %s where shortname = %s and image_set in
			(select id from ImageSet where shortname = %s)';

		$sql = sprintf($sql,
			$this->table,
			$this->db->quote($dimension_shortname, 'text'),
			$this->db->quote($set_shortname, 'text'));

		$row = SwatDB::queryRow($this->db, $sql);

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$found = true;
		}

		return $found;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('image_set',
			SwatDBClassMap::get('SiteImageSet'));

		$this->registerInternalProperty('default_type',
			SwatDBClassMap::get('SiteImageType'));

		$this->table = 'ImageDimension';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function hasSubDataObject()

	protected function hasSubDataObject($key)
	{
		$found = parent::hasSubDataObject($key);

		if ($key === 'default_type' && !$found) {
			$default_type_id = $this->getInternalValue('default_type');

			if ($default_type_id !== null &&
				array_key_exists($default_type_id, self::$default_type_cache)) {
				$this->setSubDataObject('default_type',
					self::$default_type_cache[$default_type_id]);

				$found = true;
			}
		}

		return $found;
	}

	// }}}
	// {{{ protected function setSubDataObject()

	protected function setSubDataObject($name, $value)
	{
		if ($name === 'default_type')
			self::$default_type_cache[$value->id] = $value;

		parent::setSubDataObject($name, $value);
	}

	// }}}
}

?>
