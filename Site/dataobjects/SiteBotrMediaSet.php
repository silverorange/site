<?php

require_once 'Site/dataobjects/SiteMediaSet.php';
require_once 'Site/dataobjects/SiteBotrMediaPlayerWrapper.php';
require_once 'Site/dataobjects/SiteBotrMediaEncodingWrapper.php';

/**
 * A BOTR-specific media set object
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaSet extends SiteMediaSet
{
	// {{{ public function getPlayerByShortname()

	/**
	 * Gets a player of this set based on its shortname
	 *
	 * @param string $shortname the shortname of the player
	 *
	 * @return MediaPlayer the player with the given shortname
	 */
	public function getPlayerByShortname($shortname)
	{
		foreach ($this->players as $player) {
			if ($player->shortname === $shortname) {
				return $player;
			}
		}

		throw new SiteException(sprintf('Media player “%s” does not exist.',
			$shortname));
	}

	// }}}

	// loader methods
	// {{{ protected function getEncodingWrapper()

	protected function getEncodingWrapper()
	{
		return SwatDBClassMap::get('SiteBotrMediaEncodingWrapper');
	}

	// }}}
	// {{{ protected function getMediaEncodingOrderBy()

	protected function getMediaEncodingOrderBy()
	{
		return 'width desc';
	}

	// }}}
	// {{{ protected function loadPlayers()

	/**
	 * Loads the players belonging to this set
	 *
	 * @return SiteMediaPlayerWrapper a set of player data objects
	 */
	protected function loadPlayers()
	{
		$sql = 'select * from MediaPlayer
			where media_set = %s
			order by width desc';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('SiteBotrMediaPlayerWrapper'));
	}

	// }}}
}

?>
