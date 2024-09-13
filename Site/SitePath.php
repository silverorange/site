<?php

/**
 * An ordered set of {@link SitePathEntry} objects representing a
 * path in a {@link SiteWebApplication}
 *
 * @package   Site
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SitePath implements Iterator, Countable, \Stringable
{


	/**
	 * The entries in this path
	 *
	 * This is an array of {@link SitePathEntry} objects
	 *
	 * @var array
	 *
	 * @see SitePath::addEntry()
	 */
	private $path_entries = [];

	/**
	 * The current index of the iterator interface
	 *
	 * @var integer
	 */
	private $current_index = 0;




	/**
	 * Adds an entry to this path
	 *
	 * Entries are always added to the beginning of the path. This way path
	 * strings can be parsed left-to-right and entries can be added in the
	 * same order.
	 *
	 * @param SitePathEntry $entry the entry to add.
	 */
	public final function addEntry(SitePathEntry $entry)
	{
		array_unshift($this->path_entries, $entry);
	}




	/**
	 * Appends an entry to the end of this path
	 *
	 * @param SitePathEntry $entry the entry to append.
	 */
	public final function appendEntry(SitePathEntry $entry)
	{
		$this->path_entries[] = $entry;
	}




	/**
	 * Convenience method to add path entries to a navbar
	 *
	 * @param SwatNavBar $navbar the navbar to append entries to
	 */
	public function addEntriesToNavBar(SwatNavBar $navbar)
	{
		foreach ($this->path_entries as $entry) {
			if ($entry === $this->getFirst())
				$link = $entry->shortname;
			else
				$link.= '/'.$entry->shortname;

			$navbar->createEntry($entry->title, $link);
		}
	}




	/**
	 * Whether or not this path contains the given id
	 *
	 * @return boolean true if this path contains an entry with the
	 *                  given id and false if this path does not contain
	 *                  such an entry.
	 */
	public function hasId($id)
	{
		$found = false;

		foreach ($this as $entry) {
			if ($entry->id == $id) {
				$found = true;
				break;
			}
		}

		return $found;
	}




	/**
	 * Retrieves the first entry in this path
	 *
	 * @return SitePathEntry the first entry in this path or null if there is
	 *                         no first entry (empty path).
	 */
	public function getFirst()
	{
		return $this->path_entries[0] ?? null;
	}




	/**
	 * Retrieves the last entry in this path
	 *
	 * @return SitePathEntry the last entry in this path or null if there is
	 *                         no last entry (empty path).
	 */
	public function getLast()
	{
		if (count($this) > 0)
			return $this->path_entries[count($this) - 1];

		return null;
	}




	/**
	 * Gets a string representation of this path
	 *
	 * The string is built from the shortnames of entries within this path.
	 * Each shortname is separated by a '/' character.
	 *
	 * @return string the string representation of this path.
	 */
	public function __toString(): string
	{
		$path = '';
		$first = true;

		foreach ($this as $entry) {
			if ($first)
				$first = false;
			else
				$path.= '/';

			$path.= $entry->shortname;
		}

		return $path;
	}




	/**
	 * Returns the current element
	 *
	 * @return mixed the current element.
	 */
	public function current()
	{
		return $this->path_entries[$this->current_index];
	}




	/**
	 * Returns the key of the current element
	 *
	 * @return integer the key of the current element
	 */
	public function key()
	{
		return $this->current_index;
	}




	/**
	 * Moves forward to the next element
	 */
	public function next()
	{
		$this->current_index++;
	}




	/**
	 * Rewinds this iterator to the first element
	 */
	public function rewind()
	{
		$this->current_index = 0;
	}




	/**
	 * Checks is there is a current element after calls to rewind() and next()
	 *
	 * @return boolean true if there is a current element and false if there
	 *                  is not.
	 */
	public function valid()
	{
		return isset($this->path_entries[$this->current_index]);
	}




	/**
	 * Retrieves the an object
	 *
	 * @return mixed the object or null if it does not exist
	 */
	public function get($key = 0)
	{
		return $this->path_entries[$key] ?? null;
	}




	/**
	 * Gets the number of entries in this path
	 *
	 * Satisfies the countable interface.
	 *
	 * @return integer the number of entries in this path
	 */
	public function count()
	{
		return count($this->path_entries);
	}


}

?>
