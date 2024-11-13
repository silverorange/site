<?php

/**
 * Search page for Articles.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteArticleSearch extends AdminSearch
{
    protected $join_clause;
    protected $where_clause;
    protected $order_by_clause;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->mapClassPrefixToPath('Site', 'Site');
        $this->ui->loadFromXML($this->getUiXml());

        $this->navbar->createEntry(Site::_('Search'));
    }

    protected function getUiXml()
    {
        return __DIR__ . '/search.xml';
    }

    // process phase

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

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        $processor = new SiteArticleActionsProcessor($this);
        $processor->process($view, $actions);
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $this->ui->getWidget('visibility')->addOptionsByArray(
            SiteArticleActionsProcessor::getActions()
        );
    }

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $this->searchArticles();

        $sql = sprintf(
            'select count(id) from Article %s where %s',
            $this->getJoinClause(),
            $this->getWhereClause()
        );

        $pager = $this->ui->getWidget('pager');
        $pager->total_records = SwatDB::queryOne($this->app->db, $sql);

        $sql = 'select Article.id,
					Article.title,
					Article.visible,
					Article.searchable
				from Article
				%s
				where %s
				order by %s';

        $sql = sprintf(
            $sql,
            $this->getJoinClause(),
            $this->getWhereClause(),
            $this->getOrderByClause($view, '')
        );

        $this->app->db->setLimit($pager->page_size, $pager->current_record);
        $rs = SwatDB::query($this->app->db, $sql);

        $this->ui->getWidget('results_frame')->visible = true;
        $view = $this->ui->getWidget('index_view');
        $view->getColumn('visibility')->getRendererByPosition()->db =
            $this->app->db;

        if (count($rs) > 0) {
            $this->ui->getWidget('results_message')->content =
                $pager->getResultsMessage(
                    Site::_('result'),
                    Site::_('results')
                );
        }

        return $rs;
    }

    protected function searchArticles()
    {
        $this->fulltext_result = null;
    }

    /**
     * Gets the search type for articles for this web-application.
     *
     * @return int the search type for articles for this web-application or
     *             null if fulltext searching is not implemented for the
     *             current application
     */
    protected function getSearchType()
    {
        return 'article';
    }

    protected function getJoinClause()
    {
        if ($this->join_clause === null) {
            if ($this->fulltext_result === null) {
                $this->join_clause = '';
            } else {
                $this->join_clause = $this->fulltext_result->getJoinClause(
                    'id',
                    $this->getSearchType()
                );
            }
        }

        return $this->join_clause;
    }

    protected function getWhereClause()
    {
        if ($this->where_clause === null) {
            $where = '1 = 1';

            // keywords are included in the where clause if fulltext searching
            // is turned off
            $keywords = $this->ui->getWidget('search_keywords')->value;
            if (trim($keywords) != '' && $this->getSearchType() === null) {
                $where .= ' and ( ';

                $clause = new AdminSearchClause('title');
                $clause->table = 'Article';
                $clause->value = $this->ui->getWidget('search_keywords')->value;
                $clause->operator = AdminSearchClause::OP_CONTAINS;
                $where .= $clause->getClause($this->app->db, '');

                $clause = new AdminSearchClause('bodytext');
                $clause->table = 'Article';
                $clause->value = $this->ui->getWidget('search_keywords')->value;
                $clause->operator = AdminSearchClause::OP_CONTAINS;
                $where .= $clause->getClause($this->app->db, 'or');

                $where .= ') ';
            }

            $clause = new AdminSearchClause('boolean:visible');
            $clause->value =
                $this->ui->getWidget('search_visible')->value;

            $where .= $clause->getClause($this->app->db);

            $clause = new AdminSearchClause('boolean:searchable');
            $clause->value =
                $this->ui->getWidget('search_searchable')->value;

            $where .= $clause->getClause($this->app->db);

            $this->where_clause = $where;
        }

        return $this->where_clause;
    }

    protected function getOrderByClause($view, $default_orderby)
    {
        if ($this->order_by_clause === null) {
            if ($this->fulltext_result === null) {
                $order_by_clause = 'Article.title';
            } else {
                // AdminSearch expects no 'order by' in returned value.
                $order_by_clause = str_replace(
                    'order by ',
                    '',
                    $this->fulltext_result->getOrderByClause('Article.title')
                );
            }

            $this->order_by_clause =
                parent::getOrderByClause($view, $order_by_clause);
        }

        return $this->order_by_clause;
    }
}
