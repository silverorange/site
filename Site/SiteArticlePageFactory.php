<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';
require_once 'Site/SitePageFactory.php';
require_once 'Site/SiteArticlePath.php';
require_once 'Site/pages/SiteArticlePage.php';
require_once 'Site/dataobjects/SiteArticleWrapper.php';

/**
 * Resolves and creates article pages in a web application
 *
 * @package   Site
 * @copyright 2006-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteArticlePageFactory extends SitePageFactory
{
	// {{{ protected properties

	/**
	 * The default article page decorator class to use if no article page
	 * decorator is specified for a given source string
	 *
	 * @var string
	 *
	 * @see SiteArticlePageFactory::setDefaultArticlePage()
	 */
	protected $default_article_page = 'SiteArticlePage';

	// }}}
	// {{{ public function resolvePage()

	/**
	 * Resolves a page object from a source string
	 *
	 * @param string $source the source string for which to get the page.
	 * @param SiteLayout $layout optional layout to use with this page.
	 *
	 * @return SiteAbstractPage the page for the given source string.
	 */
	public function resolvePage($source, SiteLayout $layout = null)
	{
		$layout = ($layout === null) ? $this->resolveLayout($source) : $layout;

		$page_info = $this->getPageInfo($source);

		// create page object
		$page = $this->instantiatePage($page_info['page'], $layout,
			$page_info['arguments']);

		// create article and path
		$article = $this->getArticle($page_info['path']);

		if ($article === null) {
			throw new SiteNotFoundException(
				sprintf('Article not found for path ‘%s’',
					$path));
		}

		$article_path = $this->getArticlePath($article);

		// decorate page
		$page = $this->applyDecorators($page, $page_info['decorators'],
			$article, $article_path, $page_info['arguments']);

		if (!$this->isVisible($article, $source)) {
			$page = $this->getNotVisiblePage($article, $layout);
			$page->setSource($source);
			if ($page instanceof SiteArticlePage) {
				$page->setPath($article_path);
				$page->setArticle($article);
			}
		}

		return $page;
	}

	// }}}
	// {{{ public function setDefaultArticlePage()

	/**
	 * Sets the default article page decorator class to use if no article
	 * page decorator is specified for a given source string
	 *
	 * By default, {@link SiteArticlePage} is used.
	 *
	 * @param string $class the name of the default article page decorator
	 *                      class to use if no article page decorator is
	 *                      specified for the given source string.
	 *
	 * @throws SiteClassNotFoundException if the specified class is not
	 *                                    {@link SiteArticlePage} or a subclass
	 *                                    of <code>SiteArticlePage</code>.
	 */
	public function setDefaultArticlePage($class)
	{
		$this->loadPageClass($class);

		if ($class !== 'SiteArticlePage' &&
			!is_subclass_of($class, 'SiteArticlePage')) {
			throw new SiteClassNotFoundException(sprintf('The provided page '.
				'class ‘%s’ is not a SiteArticlePage.', $class), 0, $class);
		}

		$this->default_article_page = $class;
	}

	// }}}
	// {{{ protected function applyDecorators()

	/**
	 * Applies all decorators to a page
	 *
	 * If there are no {@link SiteArticlePage} or SiteArticlePage subclass
	 * decorators, one is added automatically. All SiteArticlePage decorators
	 * are assigned the given article and path.
	 *
	 * @param SiteAbstractPage the page to which the decorators should be
	 *                          applied.
	 * @param array $decorators the decorators to apply.
	 * @param SiteArticle $article the current article.
	 * @param SiteArticlePath $path the path of the current article.
	 * @param array $arguments the arguments of the page.
	 *
	 * @return SiteAbstractPage the decorated page.
	 */
	protected function applyDecorators(SiteAbstractPage $page,
		array $decorators, SiteArticle $article, SiteArticlePath $path,
		array $arguments)
	{
		$has_article_decorator = false;
		$decorators = array_reverse($decorators);
		foreach ($decorators as $decorator) {
			$page = $this->decorate($page, $decorator);
			if ($page instanceof SiteArticlePage) {
				$has_article_decorator = true;
				$page->setPath($path);
				$page->setArticle($article);
			}
		}

		// add article decorator if none was defined in the page map
		if (!$has_article_decorator) {
			$page = $this->decorate($page, $this->default_article_page);
			$page->setPath($path);
			$page->setArticle($article);
		}

		return $page;
	}

	// }}}
	// {{{ protected function getPageInfo()

	/**
	 * Gets page info for the passed source string
	 *
	 * @param string $source the source string for which to get the page info.
	 *
	 * @return array an array of page info. The array has the index values
	 *               'page', 'path', 'decorators' and 'arguments'.
	 */
	protected function getPageInfo($source)
	{
		$info = array(
			'page'       => $this->default_page_class,
			'path'       => $source,
			'decorators' => array(),
			'arguments'  => array(),
		);

		foreach ($this->getPageMap() as $pattern => $class) {
			$regs = array();
			$pattern = str_replace('@', '\@', $pattern); // escape delimiters
			$regexp = '@'.$pattern.'@u';
			if (preg_match($regexp, $source, $regs) === 1) {
				array_shift($regs); // discard full match string

				// get path as first subpattern
				$info['path'] = array_shift($regs);

				// get additional arguments as remaining subpatterns
				foreach ($regs as $reg) {
					// set empty regs parsed from page map expressions to null
					$reg = ($reg == '') ? null : $reg;
					$info['arguments'][] = $reg;
				}

				// get page class and/or decorators
				if (is_array($class)) {
					$page = array_pop($class);
					if ($this->isPage($page)) {
						$info['page']       = $page;
						$info['decorators'] = $class;
					} else {
						$class[]            = $page;
						$info['decorators'] = $class;
					}
				} else {
					if ($this->isPage($class)) {
						$info['page'] = $class;
					} else {
						$info['decorators'][] = $class;
					}
				}

				break;
			}
		}

		return $info;
	}

	// }}}
	// {{{ protected function getPageMap()

	/**
	 * Gets an array of page mappings used to get page info
	 *
	 * The page mappings are an array of the form:
	 *
	 * <code>
	 * array(
	 *     $source expression => $page_class
	 * );
	 * </code>
	 *
	 * The <code>$source_expression</code> is an regular expression using PCRE
	 * syntax sans-delimiters. The delimiter character is unspecified and should
	 * not be escaped in these expressions. The <code>$page_class</code> is the
	 * class name of the page to be resolved.
	 *
	 * The first capturing sub-pattern of the expression is used as the
	 * path to the article in the database. Additional capturing sub-patterns
	 * are passed as arguments to the page constructor.
	 *
	 * For example, the following mapping array will match the source
	 * 'about/content' to the class 'ContactPage':
	 *
	 * <code>
	 * array(
	 *     '^(about/contact)$' => 'ContactPage',
	 * );
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
	// {{{ protected function isVisible()

	/**
	 * @param string $source
	 * @param SiteArticle $article
	 *
	 * @return boolean
	 */
	protected function isVisible(SiteArticle $article, $source)
	{
		$sql = sprintf('select count(id) from EnabledArticleView
			where id = %s',
			$this->app->db->quote($article->id, 'integer'));

		$count = SwatDB::queryOne($this->app->db, $sql);
		return ($count !== 0);
	}

	// }}}
	// {{{ protected function getNotVisiblePage()

	/**
	 * @return SiteAbstractPage
	 */
	protected function getNotVisiblePage(SiteArticle $article,
		SiteLayout $layout)
	{
		// sub-classes can return a custom page here

		// by default, throw an excpetion
		throw new SiteNotFoundException('Article not visible');
	}

	// }}}
	// {{{ protected function getArticle()

	/**
	 * Gets an article object from the database
	 *
	 * @param string $path
	 *
	 * @return SiteArticle the specified article.
	 *
	 * @throws SiteNotFoundException if no such article exists.
	 */
	protected function getArticle($path)
	{
		// don't try to resolve articles that are deeper than the max depth
		if (substr_count($path, '/') >= SiteArticle::MAX_DEPTH) {
			throw new SitePathTooLongException(
				sprintf('Article path too long: ‘%s’', $path));
		}

		// get id from path
		$article_id = $this->getArticleId($path);

		if ($article_id === null) {
			throw new SiteNotFoundException(
				sprintf('Article not found for path ‘%s’', $path));
		}

		$sql = $this->getArticleSql($article_id);

		// load dataobject
		$wrapper  = SwatDBClassMap::get('SiteArticleWrapper');
		$articles = SwatDB::query($this->app->db, $sql, $wrapper);
		$article  = $articles->getFirst();

		if ($article === null) {
			throw new SiteNotFoundException(
				sprintf('Failed to load article dataobject for article id ‘%s’',
					$article_id));
		}

		return $article;
	}

	// }}}
	// {{{ protected function getArticleId()

	/**
	 * Gets an article id from the given article path
	 *
	 * @param string $path
	 *
	 * @return integer the database identifier corresponding to the given
	 *                  article path or null if no such identifier exists.
	 */
	protected function getArticleId($path)
	{
		// don't try to find articles with invalid UTF-8 in the path
		if (!SwatString::validateUtf8($path)) {
			throw new SitePathInvalidUtf8Exception(
				sprintf('Path is not valid UTF-8: ‘%s’', $path));
		}

		// don't try to find articles with more than 254 characters in the path
		if (strlen($path) > 254) {
			throw new SitePathTooLongException(
				sprintf('Path is too long: ‘%s’', $path));
		}

		return SwatDB::executeStoredProcOne($this->app->db,
			'findArticle', array($this->app->db->quote($path, 'text')));
	}

	// }}}
	// {{{ protected function getArticleSql()

	protected function getArticleSql($article_id)
	{
		return sprintf('select * from Article where id = %s',
			$this->app->db->quote($article_id, 'integer'));
	}

	// }}}
	// {{{ protected function getArticlePath()

	protected function getArticlePath(SiteArticle $article)
	{
		return new SiteArticlePath($this->app, $article->id);
	}

	// }}}
}

?>
