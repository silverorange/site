<?php

/**
 * Interface for a fulltext search result
 *
 * @package   Site
 * @copyright 2007 silverorange
 */
interface SiteFulltextSearchResult
{
	// {{{ public function getJoinClause()

	/**
	 * Get an SQL JOIN clause to use to query with this fulltext result
	 *
	 * @param string $id_field_name name of the id field to join with.
	 * @param string $type document type to join with.
	 *
	 * @return string
	 */
	public function getJoinClause($id_field_name, $type);

	// }}}
	// {{{ public function getOrderByClause()

	/**
	 * Get an SQL ORDER BY clause to use to query with this fulltext result
	 *
	 * @param string $default_clause a default order by clause to use in
	 *                                addition to the fulltext clause.
	 *
	 * @return string
	 */
	public function getOrderByClause($default_clause);

	// }}}
	// {{{ public function getMisspellings()

	/**
	 * Retrieve possible misspellings of keywords
	 *
	 * @return array an array of possible misspellings.
	 */
	public function getMisspellings();

	// }}}
	// {{{ public function &getSearchedWords()

	/**
	 * Gets words that were entered and were searched for
	 *
	 * @return array words that were entered and were searched for.
	 */
	public function &getSearchedWords();

	// }}}
}

?>
