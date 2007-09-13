<?php

/**
 * Interface for a fulltext search engine
 *
 * @package   Site
 * @copyright 2007 silverorange
 */
interface SiteFulltextSearchEngine
{
	// {{{ public function search()

	/**
	 * Perform a fulltext search and return the result
	 *
	 * @param string $keywords the keywords to search with.
	 *
	 * @return SiteFulltextSearchResult
	 */
	public function search($keywords);

	// }}}
}

?>
