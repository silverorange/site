<?php

/**
 * Interface for a fulltext search engine.
 *
 * @copyright 2007-2016 silverorange
 */
interface SiteFulltextSearchEngine
{
    /**
     * Perform a fulltext search and return the result.
     *
     * @param string $keywords the keywords to search with
     *
     * @return SiteFulltextSearchResult
     */
    public function search($keywords);
}
