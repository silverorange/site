<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * An image type data object
 *
 * @package   Site
 * @copyright 2008 silverorange
 */
class SiteImageType extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Extension
	 *
	 * @var string
	 */
	public $extension;

	/**
	 * Mime type
	 *
	 * @var string
	 */
	public $mime_type;

	// }}}
	// {{{ public function loadByMimeType()

	/**
	 * Loads a image-type from the database with a mime-type
	 *
	 * @param string $mime_type The mime-type of the image-type
	 *
	 * @return boolean true if a set was successfully loaded and false if
	 *                  no set was found with the specified mime-type.
	 */
	public function loadByMimeType($mime_type)
	{
		$this->checkDB();

		$found = false;

		$sql = 'select * from %s where lower(mime_type) = lower(%s)';

		$sql = sprintf($sql,
			$this->table,
			$this->db->quote($mime_type, 'text'));

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
		$this->table = 'ImageType';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
