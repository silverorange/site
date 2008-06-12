<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';
require_once 'Site/SitePageFactory.php';
require_once 'Site/SiteArticlePath.php';

/**
 * Resolves and creates article pages in a web application
 *
 * @package   Site
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteArticlePageFactory extends SitePageFactory
{
	// {{{ protected properties

	/**
	 * The name of the default class to use when instantiating resolved pages
	 *
	 * This must be either {@link SiteArticlePage} or a subclass of
	 * SiteArticlePage.
	 *
	 * @var string
	 */
	protected $default_page_class = 'SiteArticlePage';

	// }}}
	// {{{ public function resolvePage()

	/**
	 * Resolves a page object from a source string
	 *
	 * @param SiteWebApplication $app the web application for which the page is
	 *                               being resolved.
	 * @param string $source the source string for which to get the page.
	 * @param SiteLayout $layout optional layout to use with this page.
	 *
	 * @return SiteArticlePage the page for the given source string.
	 */
	public function resolvePage(SiteWebApplication $app, $source, $layout = null)
	{
		$page = null;

		if ($layout === null)
			$layout = $this->resolveLayout($app, $source);

		$article_path = $source;
		foreach ($this->getPageMap() as $pattern => $class) {
			$regs = array();
			$pattern = str_replace('@', '\@', $pattern); // escape delimiters
			$regexp = '@'.$pattern.'@u';
			if (preg_match($regexp, $source, $regs) === 1) {
				array_shift($regs); // discard full match string
				$article_path = array_shift($regs);

				// set empty regs parsed from page map expressions to null
				foreach ($regs as &$reg) {
					if ($reg == '')
						$reg = null;
				}

				// add layout and application to the argument list
				array_unshift($regs, $layout);
				array_unshift($regs, $app);

				$page = $this->instantiatePage($app, $class, $regs);
				break;
			}
		}

		if ($page === null) {
			// not found in page map so instantiate default page
			$params = array($app, $layout);
			$page = $this->instantiatePage($app, $this->default_page_class, $params);
		}

		$article_id = $this->findArticle($app, $article_path);

		if ($article_id === null)
			throw new SiteNotFoundException(
				sprintf('Article not found for path ‘%s’',
					$article_path));

		$article_path = new SiteArticlePath($app, $article_id);

		$page->article_id = $article_id;
		$page->setPath($article_path);
		$page->setArticle($this->getArticle($app, $article_id, $article_path));

		if (!$this->checkVisibilty($page)) {
			$page = $this->instantiateNotVisiblePage($app, $layout);
			$page->article_id = $article_id;
			$page->setPath($article_path);
		}

		return $page;
	}

	// }}}
	// {{{ protected function getPageMap()

	/**
	 * Gets an array of page mappings used to resolve pages
	 *
	 * The page mappings are an array of the form:
	 *
	 *   source expression => page class
	 *
	 * The <i>source expression</i> is an regular expression using PCRE syntax
	 * sans-delimiters. The delimiter character is unspecified and should not
	 * be escaped in these expressions. The <i>page class</i> is the class name
	 * of the page to be resolved.
	 *
	 * For example, the following mapping array will match the source
	 * 'about/content' to the class 'ContactPage':
	 *
	 * <code>
	 * array('^(about/contact)$' => 'ContactPage');
	 * </code>
	 *
	 * By default, no page mappings are defined. Subclasses may define
	 * additional mappings by extending this method.
	 *
	 * @return array the page mappings of this factory.
	 */
	protected function getPageMap()
	{
		return array();
	}

	// }}}
	// {{{ protected function checkVisibilty()

	protected function checkVisibilty($page)
	{
		$article = null;

		$path = $page->getPath();

		if ($path !== null) {
			$path_entry = $path->getLast();
			if ($path_entry !== null) {
				$article_id = $path_entry->id;

				$sql = sprintf(
					'select id from EnabledArticleView
					where id = %s',
					$page->app->db->quote($article_id, 'integer'));

				$article = SwatDB::queryOne($page->app->db, $sql);
			}
		}

		return ($article !== null);
	}

	// }}}
	// {{{ protected function instantiateNotVisiblePage()

	protected function instantiateNotVisiblePage(SiteApplication $app,
		SiteLayout $layout)
	{
		// sub-classes can return a custom page here

		// by default, throw an excpetion
		throw new SiteNotFoundException('Article not visible');
	}

	// }}}
	// {{{ protected function findArticle()

	/**
	 * Gets an article id from the given article path
	 *
	 * @param SiteWebApplication $app
	 * @param string $path
	 *
	 * @return integer the database identifier corresponding to the given
	 *                  article path or null if no such identifier exists.
	 */
	protected function findArticle(SiteWebApplication $app, $path)
	{
		if (!SwatString::validateUtf8($path))
			throw new SiteException(
				sprintf('Path is not valid UTF-8: ‘%s’', $path));

		// trim at 254 to prevent database errors
		$path = substr($path, 0, 254);
		$sql = sprintf('select findArticle(%s)',
			$app->db->quote($path, 'text'));

		$article_id = SwatDB::queryOne($app->db, $sql);
		return $article_id;
	}

	// }}}
	// {{{ protected function getArticle()

	/**
	 * Gets an article object from the database
	 *
	 * @param integer $id the database identifier of the article to get.
	 *
	 * @return SiteArticle the specified article or null if no such article
	 *                       exists.
	 */
	protected function getArticle($app, $article_id, $path)
	{
		// don't try to resolve articles that are deeper than the max depth
		if (count($path) > SiteArticle::MAX_DEPTH)
			throw new SiteNotFoundException(
				sprintf('Article not found for path ‘%s’', $path));

		if (($article = $this->queryArticle($app->db, $article_id)) === null)
			throw new SiteNotFoundException(
				sprintf('Article dataobject failed to load for article id ‘%s’',
				$article_id));

		return $article;
	}

	// }}}
	// {{{ protected function queryArticle()

	protected function queryArticle($db, $article_id)
	{
		$sql = 'select * from Article where id = %s';

		$sql = sprintf($sql,
			$db->quote($article_id, 'integer'));

		$wrapper = SwatDBClassMap::get('SiteArticleWrapper');
		$articles = SwatDB::query($db, $sql, $wrapper);
		return $articles->getFirst();
	}

	// }}}
}

?>
