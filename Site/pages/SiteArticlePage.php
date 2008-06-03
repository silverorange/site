<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatString.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Site/pages/SitePathPage.php';
require_once 'Site/dataobjects/SiteArticle.php';
require_once 'Site/dataobjects/SiteArticleWrapper.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * A page for loading and displaying articles
 *
 * @package   Site
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteArticle
 */
class SiteArticlePage extends SitePathPage
{
	// {{{ protected properties

	/**
	 * @var SiteArticle
	 */
	protected $article;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null)
	{
		parent::__construct($app, $layout);

		$class_name = SwatDBClassMap::get('SiteArticle');
		$this->article = new $class_name();
		$this->article->setDatabase($this->app->db);
	}

	// }}}
	// {{{ public function hasParentInPath()

	/**
	 * Whether or not this page has the parent id in its path
	 *
	 * @param integer $article_id the parent article id to check.
	 *
	 * @return boolean true if this page has the given id in its path and false
	 *                  if it does not.
	 */
	public function hasParentInPath($article_id)
	{
		return $this->path->hasId($article_id);
	}

	// }}}
	// {{{ public function setArticle()

	/**
	 * Sets the article for this page to display
	 *
	 * @param SiteArticle $article the article to display.
	 */
	public function setArticle($article)
	{
		$this->article = $article;
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->layout->selected_article_id = $this->article->id;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();
		$this->buildArticle();
	}

	// }}}
	// {{{ protected function buildArticle()

	protected function buildArticle()
	{
		$this->layout->data->title =
			SwatString::minimizeEntities((string)$this->article->title);

		if ($this->article->description === null) {
			$this->layout->data->meta_description =
				SwatString::minimizeEntities(SwatString::condense(
				SwatString::stripXHTMLTags($this->article->bodytext, 400)));
		} else {
			$this->layout->data->meta_description =
				SwatString::minimizeEntities($this->article->description);
		}

		$this->buildContent();

		if (property_exists($this->layout, 'navbar'))
			$this->buildNavBar();
	}

	// }}}
	// {{{ protected function buildContent()

	protected function buildContent()
	{
		$this->layout->startCapture('content');
		$this->displayArticle($this->article);
		$this->displaySubArticles($this->article->getVisibleSubArticles());
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		if ($this->path !== null) {
			$navbar = $this->layout->navbar;
			$link = '';
			$first = true;
			foreach ($this->path as $path_entry) {
				if ($first) {
					$link.= $path_entry->shortname;
					$first = false;
				} else {
					$link.= '/'.$path_entry->shortname;
				}

				$navbar->createEntry($path_entry->title, $link);
			}
		}
	}

	// }}}
	// {{{ protected function displayArticle()

	/**
	 * Displays an article on this page
	 *
	 * Article boydtext is displayed inside a containing div element. Article
	 * bodytext may contain special markers that are replaced with content
	 * specified by SiteArticlePage subclasses.
	 *
	 * Markers are created using the following syntax inside article bodytext:
	 * <code>&lt;!-- [marker] --&gt;</code>
	 *
	 * @param SiteArticle $article the article to display.
	 *
	 * @see SiteArticlePage::getReplacementMarkerText()
	 * @see SiteArticlePage::replaceMarkers()
	 */
	protected function displayArticle(SiteArticle $article)
	{
		if (strlen($article->bodytext) > 0) {
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
	 * @see SiteArticlePage::displaySubArticle()
	 */
	protected function displaySubArticles(SiteArticleWrapper $articles,
		$path = null)
	{
		if (count($articles) == 0)
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

		if (strlen($article->description) > 0)
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
	 * @see SiteArticlePage::getReplacementMarkerText()
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
