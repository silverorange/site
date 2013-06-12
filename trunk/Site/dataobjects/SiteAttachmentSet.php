<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * An attachment set
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAttachmentSet extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * The unique identifier of this type
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The title of this type
	 *
	 * @var string
	 */
	public $title;

	/**
	 * The shortname of this type
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * @var boolean
	 */
	public $use_cdn;

	/**
	 * @var boolean
	 */
	public $obfuscate_filename;

	// }}}
	// {{{ public function loadByShortname()

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
		$this->table = 'AttachmentSet';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
