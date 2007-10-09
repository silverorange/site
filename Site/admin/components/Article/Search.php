<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'NateGoSearch/NateGoSearchQuery.php';

require_once 'include/SiteArticleActionsProcessor.php';
require_once 'include/SiteArticleVisibilityCellRenderer.php';

/**
 * Search page for Articles
 *
 * @package  Site
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteArticleSearch extends AdminSearch
{
	// {{{ protected properties

	protected $where_clause;
	protected $join_clause;
	protected $order_by_clause;

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/admin/components/Article/search.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Site', 'Site');
		$this->ui->loadFromXML($this->ui_xml);

		$this->navbar->createEntry(Site::_('Search'));
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$pager = $this->ui->getWidget('pager');
		$pager->process();

		if ($pager->getCurrentPage() > 0) {
			$disclosure = $this->ui->getWidget('search_disclosure');
			$disclosure->open = false;
		}
	}

	// }}}
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$processor = new SiteArticleActionsProcessor($this);
		$processor->process($view, $actions);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('visibility')->addOptionsByArray(
			SiteArticleActionsProcessor::getActions());
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		if ($this->where_clause === null) {
			$where = '1 = 1';

			// keywords are included in the where clause if fulltext searching
			// is turned off
			if ($this->getArticleSearchType() === null &&
				$this->ui->getWidget('search_keywords')->value != null) {

				$where.= ' and ( ';

				$clause = new AdminSearchClause('title');
				$clause->table = 'Article';
				$clause->value = $this->ui->getWidget('search_keywords')->value;
				$clause->operator = AdminSearchClause::OP_CONTAINS;
				$where.= $clause->getClause($this->app->db, '');

				$clause = new AdminSearchClause('bodytext');
				$clause->table = 'Article';
				$clause->value = $this->ui->getWidget('search_keywords')->value;
				$clause->operator = AdminSearchClause::OP_CONTAINS;
				$where.= $clause->getClause($this->app->db, 'or');

				$where.= ') ';
			}

			$clause = new AdminSearchClause('boolean:show');
			$clause->value = 
				$this->ui->getWidget('search_show')->value;

			$where.= $clause->getClause($this->app->db);

			$clause = new AdminSearchClause('boolean:searchable');
			$clause->value = 
				$this->ui->getWidget('search_searchable')->value;

			$where.= $clause->getClause($this->app->db);

			$this->where = $where;
		}

		return $this->where;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$this->searchArticles();

		$sql = sprintf('select count(id) from Article %s where %s',
			$this->join_clause,
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = 'select Article.id,
					Article.title,
					Article.show,
					Article.searchable
				from Article
				%s
				where %s
				order by %s';

		$sql = sprintf($sql,
			$this->join_clause,
			$this->getWhereClause(),
			$this->getOrderByClause($view, $this->order_by_clause));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);
		$rs = SwatDB::query($this->app->db, $sql);

		$this->ui->getWidget('results_frame')->visible = true;
		$view = $this->ui->getWidget('index_view');
		$view->getColumn('visibility')->getRendererByPosition()->db =
			$this->app->db;

		if (count($rs) > 0)
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(Site::_('result'),
					Site::_('results'));

		return $rs;
	}

	// }}}
	// {{{ protected function searchArticles()

	protected function searchArticles()
	{
		$keywords = $this->ui->getWidget('search_keywords')->value;
		if (strlen(trim($keywords)) > 0 &&
			$this->getArticleSearchType() !== null) {

			$query = new NateGoSearchQuery($this->app->db);
			$query->addDocumentType($this->getArticleSearchType());
			$query->addBlockedWords(
				NateGoSearchQuery::getDefaultBlockedWords());

			$result = $query->query($keywords);

			$this->join_clause = sprintf(
				'inner join %1$s on
					%1$s.document_id = Article.id and
					%1$s.unique_id = %2$s and %1$s.document_type = %3$s',
				$result->getResultTable(),
				$this->app->db->quote($result->getUniqueId(), 'text'),
				$this->app->db->quote($this->getArticleSearchType(),
					'integer'));

			$this->order_by_clause =
				sprintf('%1$s.displayorder1, %1$s.displayorder2, Article.title',
					$result->getResultTable());
		} else {
			$this->join_clause = '';
			$this->order_by_clause = 'Article.title';
		}
	}

	// }}}
	// {{{ protected function getArticleSearchType()

	/**
	 * Gets the search type for articles for this web-application
	 *
	 * @return integer the search type for articles for this web-application or
	 *                  null if fulltext searching is not implemented for the
	 *                  current application.
	 */
	protected function getArticleSearchType()
	{
		return null;
		//return NateGoSearch::getDocumentType($this->app->db, 'article');
	}

	// }}}
}

?>
