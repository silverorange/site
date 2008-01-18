<?php

require_once 'SwatDB/SwatDBDataObject.php';

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

	// }}}
	// {{{ public function loadByShortname()

	/**
	 * Loads a dimension from the database with a shortname
	 *
	 * @param string $shortname the shortname of the dimension
	 *
	 * @return boolean true if a dimension was successfully loaded and false if
	 *                  no dimension was found at the specified shortname.
	 */
	public function loadByShortname($shortname)
	{
		$this->checkDB();

		$found = false;

		$sql = 'select * from %s where shortname = %s';

		$sql = sprintf($sql,
			$this->table,
			$this->db->quote($shortname, 'text'));

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

		$this->table = 'ImageDimension';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
