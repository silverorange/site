<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject class for site instances
 *
 * @package   Site
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteMultipleInstanceModule
 */
class SiteInstance extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The shortname of this instance
	 *
	 * @var string
	 */
	public $shortname;

	// }}}
	// {{{ public function loadFromShortname()

	/**
	 * Loads a instance by its shortname
	 *
	 * @param string $shortname the shortname of the instance to load.
	 *
	 * @return boolean true if the instance was loaded successfully and false
	 *                  if it was not.
	 */
	public function loadFromShortname($shortname)
	{
		$row = null;

		if ($this->table !== null) {
			$sql = sprintf('select * from %s where shortname = %s',
				$this->table,
				$this->db->quote($shortname, 'text'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row === null)
			return false;

		$this->initFromRow($row);
		$this->generatePropertyHashes();
		return true;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Instance';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
