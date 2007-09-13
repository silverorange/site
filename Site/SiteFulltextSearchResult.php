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

	public function getJoinClause($id_field_name, $type);

	// }}}
	// {{{ public function getOrderByClause()

	public function getOrderByClause($default_clause);

	// }}}
}

?>
