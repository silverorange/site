<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteImageDimensionWrapper.php';

/**
 * An image set data object
 *
 * @package   Site
 * @copyright 2008 silverorange
 */
class SiteImageSet extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Short, textual identifer for this set
	 *
	 * The shortname must be unique.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * Obfuscate filename
	 *
	 * @var boolean
	 */
	public $obfuscate_filename;

	// }}}
	// {{{ public function loadByShortname()

	/**
	 * Loads a set from the database with a shortname
	 *
	 * @param string $shortname the shortname of the set
	 *
	 * @return boolean true if a set was successfully loaded and false if
	 *                  no set was found at the specified shortname.
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
		$this->table = 'ImageSet';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function loadDimensions()

	/**
	 * Loads the dimensions belonging to this set
	 *
	 * @return SiteImageDimensionWrapper a set of dimension data objects
	 */
	protected function loadDimensions()
	{
		$sql = 'select * from ImageDimension
			where image_set = %s
			order by coalesce(max_width, max_height) desc';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'));

		$wrapper = SwatDBClassMap::get('SiteImageDimensionWrapper');
		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
}

?>
