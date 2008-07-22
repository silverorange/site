<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatString.php';
require_once 'Site/dataobjects/SiteArticle.php';
require_once 'Site/pages/SitePathPage.php';

/**
 * Article page decorator
 *
 * @package   Site
 * @copyright 2004-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteArticle
 */
class SiteArticlePage extends SitePathPage
{
	// {{{ protected properties

	/**
	 * @var SiteArticle
	 *
	 * @see SiteArticlePage::setArticle()
	 */
	protected $article;

	// }}}
	// {{{ public function setArticle()

	/**
	 * Sets the article for this page to display
	 *
	 * Note: Ideally, the article would be set in the constructor of this
	 * class. A setter method exists here for backwards compatibility.
	 *
	 * @param SiteArticle $article the article to display.
	 */
	public function setArticle(SiteArticle $article)
	{
		$this->article = $article;
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->initArticle();
	}

	// }}}
	// {{{ protected function initArticle()

	protected function initArticle()
	{
		$this->layout->selected_article_id = $this->article->id;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->buildTitle();
		$this->page->build();
		$this->buildMetaDescription();
		$this->buildContent();
		$this->buildNavBar();
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		$this->layout->data->title =
			SwatString::minimizeEntities((string)$this->article->title);
	}

	// }}}
	// {{{ protected function buildMetaDescription()

	protected function buildMetaDescription()
	{
		parent::buildMetaDescription();

		if ($this->article->description === null) {
			$this->layout->data->meta_description =
				SwatString::minimizeEntities(SwatString::condense(
				SwatString::stripXHTMLTags($this->article->bodytext, 400)));
		} else {
			$this->layout->data->meta_description =
				SwatString::minimizeEntities($this->article->description);
		}
	}

	// }}}
	// {{{ protected function buildContent()

	protected function buildContent()
	{
		parent::buildContent();

		$this->layout->startCapture('content');
		$this->displayArticle($this->article);
		$this->displaySubArticles($this->article->getVisibleSubArticles());
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function displayArticle()

	/**
	 * Displays an article on this page
	 *
	 * Article boydtext is displayed inside a containing div element. Article
	 * bodytext may contain special markers that are replaced with content
	 * specified by <code>SitePage</code> subclasses.
	 *
	 * Markers are created using the following syntax inside article bodytext:
	 * <code>&lt;!-- [marker] --&gt;</code>
	 *
	 * @param SiteArticle $article the article to display.
	 *
	 * @see SitePage::getReplacementMarkerText()
	 * @see SitePage::replaceMarkers()
	 */
	protected function displayArticle(SiteArticle $article)
	{
		if ($article->bodytext != '') {
			$bodytext = (string)$article->bodytext;
			$bodytext = $this->replaceMarkers($bodytext);
			echo '<div id="article_bodytext">', $bodytext, '</div>';
		}
	}

	// }}}
	// {{{ protected function displaySubArticles()

	/**
	 * Displays a set of articles as sub-articles
	 *
	 * @param SiteArticleWrapper $articles the set of articles to display.
	 * @param string $path an optional string containing the path to the
	 *                      article being displayed.
	 *
	 * @see SitePage::displaySubArticle()
	 */
	protected function displaySubArticles(SiteArticleWrapper $articles,
		$path = null)
	{
		if (count($articles) === 0)
			return;

		echo '<dl class="sub-articles">';

		foreach($articles as $article) {
			$this->displaySubArticle($article, $path);
		}

		echo '</dl>';
	}

	// }}}
	// {{{ protected function displaySubArticle()

	/**
	 * Displays an article as a sub-article
	 *
	 * @param SiteArticle $article the article to display.
	 * @param string $path an optional string containing the path to the
	 *                      article being displayed. If no path is provided,
	 *                      the path of the current page is used.
	 */
	protected function displaySubArticle(SiteArticle $article, $path = null)
	{
		if ($path === null)
			$path = $this->path;

		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = $path.'/'.$article->shortname;
		$anchor_tag->class = 'sub-article';
		$anchor_tag->setContent($article->title);

		$dt_tag = new SwatHtmlTag('dt');
		$dt_tag->id = sprintf('sub_article_%s', $article->shortname);

		$dt_tag->open();
		$anchor_tag->display();
		$dt_tag->close();

		if ($article->description != '')
			echo '<dd>', $article->description, '</dd>';
	}

	// }}}
	// {{{ protected function getReplacementMarkerText()

	/**
	 * Gets replacement text for a specfied replacement marker identifier
	 *
	 * @param string $marker_id the id of the marker found in the article
	 *                           bodytext.
	 *
	 * @return string the replacement text for the given marker id.
	 */
	protected function getReplacementMarkerText($marker_id)
	{
		// by default, always return a blank string as replacement text
		return '';
	}

	// }}}
	// {{{ protected final function replaceMarkers()

	/**
	 * Replaces markers in article with dynamic content
	 *
	 * @param string $text the bodytext of the article.
	 *
	 * @return string the article bodytext with markers replaced by dynamic
	 *                 content.
	 *
	 * @see SitePage::getReplacementMarkerText()
	 */
	protected final function replaceMarkers($text)
	{
		$marker_pattern = '/<!-- \[(.*?)\] -->/u';
		$callback = array($this, 'getReplacementMarkerTextByMatches');
		return preg_replace_callback($marker_pattern, $callback, $text);
	}

	// }}}
	// {{{ private final function getReplacementMarkerTextByMatches()

	/**
	 * Gets replacement text for a replacement marker from within a matches
	 * array returned from a PERL regular expression
	 *
	 * @param array $matches the PERL regular expression matches array.
	 *
	 * @return string the replacement text for the first parenthesized
	 *                 subpattern of the <i>$matches</i> array.
	 */
	private final function getReplacementMarkerTextByMatches($matches)
	{
		if (isset($matches[1]))
			return $this->getReplacementMarkerText($matches[1]);

		return '';
	}

	// }}}
}

?>
