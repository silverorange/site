<?php

require_once 'Site/SiteSearchEngine.php';
require_once 'Site/dataobjects/SiteArticleWrapper.php';

/**
 * An article search engine
 *
 * @package   Site
 * @copyright 2007-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteArticleSearchEngine extends SiteSearchEngine
{
	// {{{ protected function getResultWrapperClass()

	protected function getResultWrapperClass()
	{
		$wrapper_class = SwatDBClassMap::get('SiteArticleWrapper');

		return $wrapper_class;
	}

	// }}}
	// {{{ protected function getSelectClause()

	protected function getSelectClause()
	{
		$clause = 'select Article.*';

		return $clause;
	}

	// }}}
	// {{{ protected function getFromClause()

	protected function getFromClause()
	{
		$clause = 'from Article';

		if ($this->fulltext_result !== null)
			$clause.= ' '.
				$this->fulltext_result->getJoinClause(
					'Article.id', 'article');

		return $clause;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$clause = sprintf('where Article.searchable = %s',
			$this->app->db->quote(true, 'boolean'));

		return $clause;
	}

	// }}}
	// {{{ protected function getOrderByClause()

	protected function getOrderByClause()
	{
		if ($this->fulltext_result === null)
			$clause = sprintf('order by Article.title');
		else
			$clause =
				$this->fulltext_result->getOrderByClause('Article.title');

		return $clause;
	}

	// }}}
}

?>
