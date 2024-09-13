<?php

/**
 * Interface for a fulltext search result.
 *
 * @copyright 2007-2016 silverorange
 */
interface SiteFulltextSearchResult
{
    /**
     * Get an SQL JOIN clause to use to query with this fulltext result.
     *
     * @param string $id_field_name name of the id field to join with
     * @param string $type          document type to join with
     *
     * @return string
     */
    public function getJoinClause($id_field_name, $type);

    /**
     * Get an SQL ORDER BY clause to use to query with this fulltext result.
     *
     * @param string $default_clause a default order by clause to use in
     *                               addition to the fulltext clause
     *
     * @return string
     */
    public function getOrderByClause($default_clause);

    /**
     * Retrieve possible misspellings of keywords.
     *
     * @return array an array of possible misspellings
     */
    public function getMisspellings();

    /**
     * Gets words that were entered and were searched for.
     *
     * @return array words that were entered and were searched for
     */
    public function &getSearchedWords();

    /**
     * Saves this search result for search statistics and tracking.
     */
    public function saveHistory();
}
