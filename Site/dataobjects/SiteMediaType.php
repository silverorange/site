<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A media type object
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaType extends SwatDBDataObject
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
	 * Loads a media type from the database with a mime-type
	 *
	 * @param string $mime_type The mime-type of the media type
	 *
	 * @return boolean true if a type was successfully loaded and false if
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
		$this->table = 'MediaType';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
