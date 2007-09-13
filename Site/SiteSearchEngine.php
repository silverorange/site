<?php

require_once 'Swat/SwatObject.php';
require_once 'Site/SiteFulltextSearchResult.php';

/**
 * An abstract search engine
 *
 * @package   Site
 * @copyright 2007 silverorange
 */
abstract class SiteSearchEngine extends SwatObject
{
	// {{{ protected properties

	/**
	 * The application object
	 *
	 * @var SiteApplication
	 */
	protected $app;

	/**
	 * A fulltext result object
	 *
	 * @var SiteFulltextSearchResult 
	 */
	protected $fulltext_result;

	/**
	 * Total number of results
	 *
	 * @var integer
	 */
	protected $result_count;

	// }}}

	// {{{ public function __construct()

	/**
	 * Creates a new search engine
	 *
	 * @param SiteApplication $app the application object.
	 */
	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ public function setFulltextResult()

	/**
	 * Sets a fulltext result object to use when searching
	 *
	 * @param SiteFulltextSearchResult $result a fulltext search result object.
	 */
	public function setFulltextResult(SiteFulltextSearchResult $result = null)
	{
		$this->fulltext_result = $result;
	}

	// }}}
	// {{{ public function search()

	/**
	 * Performs a search and returns the results
	 *
	 * @param integer $limit maximum number of results to return.
	 * @param integer $offset skip over this many results before returning any.
	 *
	 * @return SwatDBRecordsetWrapper a recordset containing result objects.
	 */
	public function search($limit = null, $offset = null)
	{
		$select_clause = $this->getSelectClause();
		$from_clause = $this->getFromClause();
		$where_clause = $this->getWhereClause();
		$order_by_clause = $this->getOrderByClause();
		$limit_clause = $this->getLimitClause($limit);
		$offset_clause = $this->getOffsetClause($offset);

		$this->result_count = $this->queryResultCount($from_clause, $where_clause);

		$results = $this->queryResults($select_clause, $from_clause,
			$where_clause, $order_by_clause, $limit_clause, $offset_clause);

		return $results;
	}

	// }}}
	// {{{ public function getResultCount()

	/**
	 * Retrieve the total number of results available
	 *
	 * @return integer the total number of results available.
	 */
	public function getResultCount()
	{
		if ($this->result_count === null)
			throw new SiteException('Unable to retrieve result count. '.
				'Results must be retrieved first by calling '.
				'SiteNateGoSearchEngine::getResults().');

		return $this->result_count;
	}

	// }}}
	// {{{ protected function queryResults()

	/**
	 * Query for results
	 *
	 * @param string $select_clause the SQL SELECT clause to query with.
	 * @param string $from_clause the SQL FROM clause to query with.
	 * @param string $where_clause the SQL WHERE clause to query with.
	 * @param string $order_by_clause the SQL ORDER BY clause to query with.
	 * @param string $limit_clause the SQL LIMIT clause to query with.
	 * @param string $offset_clause the SQL OFFSET clause to query with.
	 *
	 * @return SwatDBRecordsetWrapper the results.
	 */
	protected function queryResults($select_clause, $from_clause,
		$where_clause, $order_by_clause, $limit_clause, $offset_clause)
	{
		$sql = sprintf('%1$s %2$s %3$s %4$s %5$s %6$s',
			$select_clause,
			$from_clause,
			$where_clause,
			$order_by_clause,
			$limit_clause,
			$offset_clause);

		$wrapper_class = $this->getResultWrapperClass();
		$results = SwatDB::query($this->app->db, $sql, $wrapper_class);

		return $results;
	}

	// }}}
	// {{{ protected function queryResultCount()

	/**
	 * Query for the total number of results
	 *
	 * @param string $from_clause the SQL FROM clause to query with.
	 * @param string $where_clause the SQL WHERE clause to query with.
	 *
	 * @return integer the total number of results.
	 */
	protected function queryResultCount($from_clause, $where_clause)
	{
		$sql = sprintf('select count(0) %s %s',
			$from_clause,
			$where_clause);

		$count = SwatDB::queryOne($this->app->db, $sql);

		return $count;
	}

	// }}}
	// {{{ abstract protected function getResultWrapperClass()

	/**
	 * Retrieve the name of the wrapper class to use for results
	 *
	 * @return string the name of the result wrapper class.
	 */
	abstract protected function getResultWrapperClass();

	// }}}
	// {{{ abstract protected function getSelectClause()

	/**
	 * Retrieve the SQL SELECT clause to query results with
	 *
	 * @return string the SQL clause.
	 */
	abstract protected function getSelectClause();

	// }}}
	// {{{ abstract protected function getFromClause()

	/**
	 * Retrieve the SQL FROM clause to query results with
	 *
	 * @return string the SQL clause.
	 */
	abstract protected function getFromClause();

	// }}}
	// {{{ protected function getOrderByClause()

	/**
	 * Retrieve the SQL ORDER BY clause to query results with
	 *
	 * @return string the SQL clause.
	 */
	protected function getOrderByClause()
	{
		$clause = '';

		return $clause;
	}

	// }}}
	// {{{ protected function getWhereClause()

	/**
	 * Retrieve the SQL WHERE clause to query results with
	 *
	 * @return string the SQL clause.
	 */
	protected function getWhereClause()
	{
		$clause = 'where 1 = 1';

		return $clause;
	}

	// }}}
	// {{{ protected function getOffsetClause()

	/**
	 * Retrieve the SQL OFFSET clause to query results with
	 *
	 * @return string the SQL clause.
	 */
	protected function getOffsetClause($offset)
	{
		$clause = '';

		if ($offset !== null)
			$clause = sprintf('offset %s',
				$this->app->db->quote($offset, 'integer'));

		return $clause;
	}

	// }}}
	// {{{ protected function getLimitClause()

	/**
	 * Retrieve the SQL LIMIT clause to query results with
	 *
	 * @return string the SQL clause.
	 */
	protected function getLimitClause($limit)
	{
		$clause = '';

		if ($limit !== null)
			$clause = sprintf('limit %s',
				$this->app->db->quote($limit, 'integer'));

		return $clause;
	}

	// }}}
}

?>
