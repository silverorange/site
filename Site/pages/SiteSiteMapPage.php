<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Site/pages/SiteArticlePage.php';

/**
 * @package   Site
 * @copyright 2006 silverorange
 */
class SiteSiteMapPage extends SiteArticlePage
{
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$wrapper = SwatDBClassMap::get('SiteArticleWrapper');
		$sql =
			'select id, title, shortname from Article where parent is null';

		$articles = SwatDB::query($this->app->db, $sql, $wrapper);
		$articles->setRegion($this->app->getRegion());

		$this->layout->startCapture('content');
		$this->displaySubArticles($articles);
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function displaySubArticle()

	protected function displaySubArticle($article, $path = null)
	{
		if ($path === null)
			$path = $article->shortname;
		else
			$path.= '/'.$article->shortname;

		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = $path;
		$anchor_tag->class = 'sub-article';
		$anchor_tag->setContent($article->title);
		$anchor_tag->display();

		if ($this->showSubArticles($path))
			$this->displaySubArticles($article->sub_articles, $path);
	}

	// }}}
	// {{{ protected function showSubArticles()

	protected function showSubArticles($path)
	{
		return true;
	}

	// }}}
}

?>
