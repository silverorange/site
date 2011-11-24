<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteBotrMediaSet.php';

/**
 * A BOTR-specific media player object
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaPlayer extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * The unique identifier of this media player
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Short textual identifer for this player
	 *
	 * The shortname must be unique within this player's set.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * BOTR key
	 *
	 * @var string
	 */
	public $key;

	/**
	 * Width in pixels
	 *
	 * @var integer
	 */
	public $width;

	/**
	 * Height in pixels
	 *
	 * @var integer
	 */
	public $height;

	// }}}
	// {{{ public function loadByShortname()

	/**
	 * Loads a player from the database with a shortname
	 *
	 * @param string $set_shortname the shortname of the set
	 * @param string $player_shortname the shortname of the player
	 * @param SiteInstance $instance optional instance
	 *
	 * @return boolean true if a player was successfully loaded and false if
	 *                  no player was found at the specified shortname.
	 */
	public function loadByShortname($set_shortname, $player_shortname,
		SiteInstance $instance = null)
	{
		$this->checkDB();

		$found = false;

		$sub_sql = sprintf('select id from MediaSet where shortname = %s',
			$this->db->quote($set_shortname, 'text'));

		if ($instance instanceof SiteInstance)
			$sub_sql.= sprintf(' and (instance is null or instance = %s)',
				$instance->id);

		$sql = 'select * from %s where shortname = %s and media_set in (%s)';

		$sql = sprintf($sql,
			$this->table,
			$this->db->quote($player_shortname, 'text'),
			$sub_sql);

		$row = SwatDB::queryRow($this->db, $sql);

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$found = true;
		}

		return $found;
	}

	// }}}
	// {{{ public function getDimensions()

	/**
	 * Returns the dimensions of the player.
	 *
	 * @return array the dimensions of the player as array of width, height.
	 */
	public function getDimensions()
	{
		return array($this->width, $this->height);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('media_set',
			SwatDBClassMap::get('SiteBotrMediaSet'));

		$this->table = 'MediaPlayer';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
