<?php

require_once 'Swat/SwatObject.php';
require_once 'Site/SiteFulltextSearchResult.php';
require_once 'Site/SiteMemcacheModule.php';

/**
 * An abstract search engine
 *
 * @package   Site
 * @copyright 2007-2008 silverorange
 */
abstract class SiteSearchEngine extends SwatObject
{
	// {{{ protected properties

	/**
	 * The application object
	 *
	 * @var SiteApplication
	 *
	 * @see SiteSearchEngine::__construct()
	 */
	protected $app;

	/**
	 * A fulltext result object
	 *
	 * @var SiteFulltextSearchResult
	 */
	protected $fulltext_result;

	/**
	 * Order by fields of this search engine
	 *
	 * This array is sorted with the highest priority order first.
	 *
	 * @var array
	 *
	 * @see SiteSearchEngine::addOrderByField()
	 */
	protected $order_by_fields = array();

	/**
	 * Array of cached search result counts
	 *
	 * Array is indexed by the search parameter hash and values are the counts.
	 *
	 * @var array
	 *
	 * @see SiteSearchEngine::getResultCount()
	 * @see SiteSearchEngine::getSearchParameterHash()
	 */
	protected $result_count_cache = array();

	protected $select_fields = array('*');

	protected $memcahe;

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

		if ($app->hasModule('SiteMemcacheModule')) {
			$this->memcache = $app->getModule('SiteMemcacheModule');
		}
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

		$results = $this->queryResults($select_clause, $from_clause,
			$where_clause, $order_by_clause, $limit_clause, $offset_clause);

		return $results;
	}

	// }}}
	// {{{ public function getResultCount()

	/**
	 * Gets the total number of results available
	 *
	 * @return integer the total number of results available.
	 */
	public function getResultCount()
	{
		$hash = $this->getSearchParameterHash();
		if (array_key_exists($hash, $this->result_count_cache)) {
			$count = $this->result_count_cache[$hash];
		} else {
			$from_clause = $this->getFromClause();
			$where_clause = $this->getWhereClause();
			$count = $this->queryResultCount($from_clause, $where_clause);
			$this->result_count_cache[$hash] = $count;
		}

		return $count;
	}

	// }}}
	// {{{ public function getSearchSummary()

	/**
	 * Get a summary of the criteria that was used to perform the search
	 *
	 * @return array an array of summary strings.
	 */
	public function getSearchSummary()
	{
		$summary = array();

		if ($this->fulltext_result !== null) {
			$keywords = implode(' ', $this->fulltext_result->getSearchedWords());
			$summary[] = sprintf('Keywords: <b>%s</b>',
				SwatString::minimizeEntities($keywords));
		}

		return $summary;
	}

	// }}}
	// {{{ public function addOrderByField()

	/**
	 * Adds an order by field to the order by clause
	 *
	 * Added fields take priority over previous added fields. For example, if
	 * the field id is added and then the field displayorder is added, results
	 * will be ordered by displayorder and then id.
	 *
	 * @param string $field the order by field. Example fields are:
	 *                      'Product.id desc', 'shortname', and
	 *                      'Category.displayorder'.
	 */
	public function addOrderByField($field)
	{
		array_unshift($this->order_by_fields, $field);
	}

	// }}}
	// {{{ public function setSelectFields()

	public function setSelectFields(array $fields = array('*'))
	{
		$this->select_fields = $fields;
	}

	// }}}
	// {{{ protected function queryResults()

	/**
	 * Performs a query for search results
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

		$results = false;

		if ($this->hasMemcache()) {
			$key = $this->getResultsCacheKey($sql);
			$ns  = $this->getMemcacheNs();
			$ids = $this->memcache->getNs($ns, $key);

			if ($ids !== false) {
				$wrapper_class = $this->getResultWrapperClass();
				$results = new $wrapper_class();
				if (count($ids) > 0) {
					$cached_results = $this->memcache->getNs($ns, $ids);
					if (count($cached_results) !== count($ids)) {
						$results = false;
					} else {
						foreach ($cached_results as $result) {
							$results->add($result);
						}
					}
				}

				if ($results !== false) {
					$results->setDatabase($this->app->db);
				}
			}
		}

		if ($results === false) {
			$wrapper_class = $this->getResultWrapperClass();
			$results = SwatDB::query($this->app->db, $sql, $wrapper_class);

			$this->loadSubObjects($results);

			if ($this->hasMemcache()) {
				$ids = array();
				foreach ($results as $id => $result) {
					$result_key = $key.'.'.$id;
					$ids[] = $result_key;
					$this->memcache->setNs($ns, $result_key, $result);
				}
				$this->memcache->setNs($ns, $key, $ids);
			}
		}

		return $results;
	}

	// }}}
	// {{{ protected function loadSubObjects()

	protected function loadSubObjects(SwatDBRecordsetWrapper $results)
	{
	}

	// }}}
	// {{{ protected function queryResultCount()

	/**
	 * Performs a query for the total number of search results
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

		$count = false;

		if ($this->hasMemcache()) {
			$key   = $this->getResultCountCacheKey($sql);
			$ns    = $this->getMemcacheNs();
			$count = $this->memcache->getNs($ns, $key);
		}

		if ($count === false) {
			$count = SwatDB::queryOne($this->app->db, $sql);
			if ($this->hasMemcache()) {
				$this->memcache->setNs($ns, $key, $count);
			}
		}

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
	// {{{ protected function getMemcacheNs()

	protected function getMemcacheNs()
	{
		return null;
	}

	// }}}
	// {{{ protected function hasMemcache()

	protected function hasMemcache()
	{
		return ($this->memcache instanceof SiteMemcacheModule &&
			$this->getMemcacheNs() !== null);
	}

	// }}}
	// {{{ protected function getOrderByClause()

	/**
	 * Gets the SQL order by clause to query results with
	 *
	 * @return string the SQL order by clause.
	 *
	 * @see SiteSearchEngine::addOrderByField()
	 */
	protected function getOrderByClause()
	{
		if (count($this->order_by_fields) == 0) {
			$clause = '';
		} else {
			$clause = 'order by '.implode(', ', $this->order_by_fields);
		}

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
	// {{{ protected function getSearchParameterHash()

	/**
	 * Gets a hash value for the public properties (search parameters) of this
	 * search engine
	 *
	 * The hash value is used to identify cached search results.
	 *
	 * @return string a hash value for the public properties of this search
	 *                 engine.
	 */
	protected function getSearchParameterHash()
	{
		$properties = $this->getPublicProperties();
		return md5(serialize($properties));
	}

	// }}}
	// {{{ protected function getPublicProperties()

	/**
	 * Gets all the public properties (search parameters) of this search engine
	 *
	 * @return array an associative array of public properties of this search
	 *               engine. The array key is the property name and the array
	 *               value is the property value.
	 */
	protected function getPublicProperties()
	{
		$public_properties = array();
		$reflector = new ReflectionClass(get_class($this));
		foreach ($reflector->getProperties() as $property) {
			if ($property->isPublic() && !$property->isStatic()) {
				$public_properties[$property->getName()] =
					$property->getValue($this);
			}
		}
		return $public_properties;
	}

	// }}}
	// {{{ protected function getResultsCacheKey()

	protected function getResultsCacheKey($sql)
	{
		return md5($sql);
	}

	// }}}
	// {{{ protected function getResultCountCacheKey()

	protected function getResultCountCacheKey($sql)
	{
		return md5($sql);
	}

	// }}}
	// {{{ protected function getResultIds()

	protected function getResultIds(SwatDBRecordsetWrapper $results)
	{
		$result_ids = array();
		foreach ($results as $result) {
			$result_ids[] = $result->id;
		}
		return $result_ids;
	}

	// }}}
}

?>
