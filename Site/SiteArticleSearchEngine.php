<?php

/**
 * An article search engine.
 *
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteArticleSearchEngine extends SiteSearchEngine
{
    protected function getResultWrapperClass()
    {
        return SwatDBClassMap::get(SiteArticleWrapper::class);
    }

    protected function getSelectClause()
    {
        return 'select Article.*';
    }

    protected function getFromClause()
    {
        $clause = 'from Article';

        if ($this->fulltext_result !== null) {
            $clause .= ' ' .
                $this->fulltext_result->getJoinClause(
                    'Article.id',
                    'article'
                );
        }

        return $clause;
    }

    protected function getWhereClause()
    {
        return sprintf(
            'where Article.searchable = %s',
            $this->app->db->quote(true, 'boolean')
        );
    }

    protected function getOrderByClause()
    {
        if ($this->fulltext_result === null) {
            $clause = sprintf('order by Article.title');
        } else {
            $clause =
                $this->fulltext_result->getOrderByClause('Article.title');
        }

        return $clause;
    }

    protected function getMemcacheNs()
    {
        return 'article';
    }
}
