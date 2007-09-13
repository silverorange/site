<?php

require_once 'Site/SiteFulltextSearchResult.php';
require_once 'NateGoSearch/NateGoSearchResult.php';

/**
 * A fulltext search result that uses NateGoSearch
 *
 * @package   Site
 * @copyright 2007 silverorange
 */
class SiteNateGoFulltextSearchResult extends SwatObject
	implements SiteFulltextSearchResult
{
	// {{{ protected properties

	/**
	 * The database
	 *
	 * @var MDB2_Driver_Common
	 */
	protected $db;

	/**
	 * The nate-go result object
	 *
	 * @var NateGoSearchResult
	 */
	protected $nate_go_result;

	// }}}

	// {{{ public function __construct()

	/**
	 * Creates a new nate-go fulltext search result
	 *
	 * @param MDB2_Driver_Common $db the database.
	 * @param NateGoSearchResult $result a NateGoSearchResult object.
	 */
	public function __construct($db, $result)
	{
		$this->db = $db;
		$this->nate_go_result = $result;
	}

	// }}}
	// {{{ public function getJoinClause()

	public function getJoinClause($id_field_name, $type)
	{
		$type = $this->nate_go_result->getDocumentType($type);

		$clause = sprintf('inner join %1$s on
			%1$s.document_id = %2$s and
			%1$s.unique_id = %3$s and %1$s.document_type = %4$s',
			$this->nate_go_result->getResultTable(),
			$id_field_name,
			$this->db->quote($this->nate_go_result->getUniqueId(), 'text'),
			$this->db->quote($type, 'integer'));

		return $clause;
	}

	// }}}
	// {{{ public function getOrderByClause()

	public function getOrderByClause($default_clause)
	{
		$clause = sprintf('order by %1$s.displayorder1, 
			%1$s.displayorder2, %2$s',
			$this->nate_go_result->getResultTable(),
			$default_clause);

		return $clause;
	}

	// }}}
	// {{{ public function getMisspellings()

	public function getMisspellings()
	{
		return $this->nate_go_result->getMisspellings();
	}

	// }}}
}

?>
