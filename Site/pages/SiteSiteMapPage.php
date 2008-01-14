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

		$this->layout->startCapture('content');
		$this->displaySubArticles($this->queryArticles());
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function queryArticles()

	protected function queryArticles()
	{
		$wrapper = SwatDBClassMap::get('SiteArticleWrapper');
		$sql = 'select id, title, shortname from Article
			where parent is null and enabled = %1$s and show = %1$s
			order by displayorder, title';

		$sql = sprintf($sql, $this->app->db->quote(true, 'boolean'));

		$articles = SwatDB::query($this->app->db, $sql, $wrapper);

		return $articles;
	}

	// }}}
	// {{{ protected function displaySubArticles()

	protected function displaySubArticles(SiteArticleWrapper $articles,
		$path = null)
	{
		if (count($articles) == 0)
			return;

		echo '<ul class="site-map-list">';

		foreach($articles as $article)
			$this->displaySubArticle($article, $path);

		echo '</ul>';
	}

	// }}}
	// {{{ protected function displaySubArticle()

	protected function displaySubArticle($article, $path = null)
	{
		if ($path === null)
			$path = $article->shortname;
		else
			$path.= '/'.$article->shortname;

		if ($this->showArticle($path)) {
			echo '<li>';
			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = $path;
			$anchor_tag->class = 'sub-article';
			$anchor_tag->setContent($article->title);
			$anchor_tag->display();

			if ($this->showSubArticles($path))
				$this->displaySubArticles($article->sub_articles, $path);

			echo '</li>';
		}
	}

	// }}}
	// {{{ protected function showArticle()

	protected function showArticle($path)
	{
		return true;
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
