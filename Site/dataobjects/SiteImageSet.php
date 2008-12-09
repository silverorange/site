<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteImageDimensionWrapper.php';
require_once 'Swat/SwatHtmlTag.php';

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
	// {{{ public function hasDimension()

	/**
	 * Checks existance of a dimension by its shortname
	 *
	 * @param string $shortname the shortname of the dimension
	 *
	 * @return boolean whether the dimension with the given shortname exists
	 */
	public function hasDimension($shortname)
	{
		$found = false;

		foreach ($this->dimensions as $dimension) {
			if ($dimension->shortname === $shortname) {
				$found = true;
				break;
			}
		}

		return $found;
	}

	// }}}
	// {{{ public function getDimensionByShortname()

	/**
	 * Gets a dimension of this set based on its shortname
	 *
	 * @param string $shortname the shortname of the dimension
	 *
	 * @return SiteImageDimension the image dimension with the given shortname
	 */
	public function getDimensionByShortname($shortname)
	{
		foreach ($this->dimensions as $dimension)
			if ($dimension->shortname === $shortname)
				return $dimension;

		throw new SwatException(sprintf('Image dimension “%s” does not exist.',
			$shortname));
	}

	// }}}
	// {{{ public function getPlaceholderImage()

	/**
	 * Gets a placeholder image tag for a specific dimension
	 *
	 * @param string $shortname the shortname of the dimension
	 * @param string $uri the uri of the placeholder image
	 *
	 * @return SwatHtmlTag the placeholder image tag
	 */
	public function getPlaceholderImage($shortname, $uri)
	{
		$dimension = $this->getDimensionByShortname($shortname);

		$img_tag = new SwatHtmlTag('img');
		$img_tag->src = $uri;
		$img_tag->width = $dimension->max_width;
		$img_tag->height = $dimension->max_height;

		return $img_tag;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'ImageSet';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function getImageDimensionClassName()

	protected function getImageDimensionWrapperClassName()
	{
		return SwatDBClassMap::get('SiteImageDimensionWrapper');
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		return array(
			'dimensions',
		);
	}

	// }}}

	// loader methods
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

		$wrapper = $this->getImageDimensionWrapperClassName();
		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
}

?>
