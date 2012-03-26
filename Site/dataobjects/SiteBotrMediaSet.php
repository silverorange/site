<?php

require_once 'Site/dataobjects/SiteMediaSet.php';
require_once 'Site/dataobjects/SiteBotrMediaPlayerWrapper.php';
require_once 'Site/dataobjects/SiteBotrMediaEncodingWrapper.php';

/**
 * A BOTR-specific media set object
 *
 * @package   Site
 * @copyright 2011-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaSet extends SiteMediaSet
{
	// {{{ public function hasEncodingByKey()

	/**
	 * Checks existance of an encoding by its shortname
	 *
	 * @param string $key the key of the encoding
	 *
	 * @return boolean whether the encoding with the given shortname exists
	 */
	public function hasEncodingByKey($key)
	{
		$found = false;

		foreach ($this->encodings as $encoding) {
			if ($encoding->key === $key) {
				$found = true;
				break;
			}
		}

		return $found;
	}

	// }}}
	// {{{ public function getEncodingByKey()

	/**
	 * Gets an encoding of this set based on its shortname
	 *
	 * @param string $key the key of the encoding
	 *
	 * @return SiteBotrMediaEncoding the encoding with the given shortname
	 */
	public function getEncodingByKey($key)
	{
		foreach ($this->encodings as $encoding) {
			if ($encoding->key === $key) {
				return $encoding;
			}
		}

		throw new SiteException(sprintf('Media encoding “%s” does not exist.',
			$key));
	}

	// }}}
	// {{{ public function getEncodingShortnameByKey()

	/**
	 * Gets the shortname of an encoding of this set based on its key
	 *
	 * @param string $key the key of the encoding
	 *
	 * @return string the shortname of the encoding
	 */
	public function getEncodingShortnameByKey($key)
	{
		foreach ($this->encodings as $encoding) {
			if ($encoding->key === $key) {
				return $encoding->shortname;
			}
		}

		throw new SiteException(sprintf('Media encoding “%s” does not exist.',
			$key));
	}

	// }}}
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
	// }}}
	// {{{ protected function getMediaEncodingWrapperClass()

	protected function getMediaEncodingWrapperClass()
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
