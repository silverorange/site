<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatString.php';
require_once 'Site/dataobjects/SiteArticle.php';
require_once 'Site/layouts/SiteLayout.php';
require_once 'Site/SiteObject.php';
require_once 'Site/SiteApplication.php';
require_once 'Site/SitePath.php';

/**
 * Base class for a page
 *
 * @package   Site
 * @copyright 2004-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteArticle
 */
class SitePage extends SiteObject
{
	// {{{ public properties

	/**
	 * Application object
	 *
	 * A reference to the {@link SiteApplication} object that created
	 * this page.
	 *
	 * @var SiteApplication
	 */
	public $app = null;

	/**
	 * @var SiteLayout
	 *
	 * @see SitePage::__construct()
	 * @see SitePage::createLayout()
	 */
	public $layout = null;

	// }}}
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $source = null;

	/**
	 * @var SiteArticle
	 *
	 * @see SitePage::setArticle()
	 */
	protected $article;

	/**
	 * @var SitePath
	 *
	 * @see SitePage::setPath()
	 * @see SitePage::getPath()
	 */
	protected $path;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null)
	{
		$this->app = $app;

		if ($layout === null)
			$this->layout = $this->createLayout();
		else
			$this->layout = $layout;
	}

	// }}}
	// {{{ public function setSource()

	public function setSource($source)
	{
		$this->source = $source;
	}

	// }}}
	// {{{ public function getSource()

	public function getSource()
	{
		return $this->source;
	}

	// }}}
	// {{{ public function setPath()

	/**
	 * Sets the path of this page
	 *
	 * @param SitePath $path
	 */
	public function setPath(SitePath $path)
	{
		$this->path = $path;
	}

	// }}}
	// {{{ public function getPath()

	/**
	 * Gets the path of this page
	 *
	 * @return SitePath the path of this page.
	 */
	public function getPath()
	{
		return $this->path;
	}

	// }}}
	// {{{ public function setArticle()

	/**
	 * Sets the article for this page to display
	 *
	 * @param SiteArticle $article the article to display.
	 */
	public function setArticle(SiteArticle $article)
	{
		$this->article = $article;
	}

	// }}}
	// {{{ public function hasParentInPath()

	/**
	 * Whether or not this page has the parent id in its path
	 *
	 * @param integer $id the parent id to check.
	 *
	 * @return boolean true if this page has the given id in its path and false
	 *                  if it does not.
	 */
	public function hasParentInPath($id)
	{
		$has_parent_in_path = false;

		if ($this->path instanceof SitePath)
			$has_parent_in_path = true;

		return $has_parent_in_path;
	}

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout($this->app, 'Site/layouts/xhtml/default.php');
	}

	// }}}

	// init phase
	// {{{ public function init()

	/**
	 * The first page method that is run by a {@link SiteWebApplication}.
	 * Always runs before {@link SiteLayout::init()}. This method is intended
	 * to initialize objects used by the {@link SitePage::process()} and
	 * {@link SitePage::build()} methods.
	 */
	public function init()
	{
		if ($this->article instanceof SiteArticle)
			$this->initArticle();
	}

	// }}}
	// {{{ protected function initArticle()

	protected function initArticle()
	{
		$this->layout->selected_article_id = $this->article->id;
	}

	// }}}

	// process phase
	// {{{ public function process()

	/**
	 * Always runs after {@link SitePage::init()} and before
	 * {@link SiteLayout::process()}. This method is intended to process
	 * data entered by the user.
	 */
	public function process()
	{
	}

	// }}}

	// build phase
	// {{{ public function build()

	/**
	 * Always runs after {@link SitePage::process()} and before
	 * {@link SiteLayout::build()}. This method is intended to build page
	 * content and add it to the layout.
	 */
	public function build()
	{
		$this->buildTitle();
		$this->buildMetaDescription();
		$this->buildNavBar();
		$this->buildContent();
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		if ($this->article instanceof SiteArticle) {
			$this->layout->data->title =
				SwatString::minimizeEntities((string)$this->article->title);
		}
	}

	// }}}
	// {{{ protected function buildMetaDescription()

	protected function buildMetaDescription()
	{
		if ($this->article instanceof SiteArticle) {
			if ($this->article->description === null) {
				$this->layout->data->meta_description =
					SwatString::minimizeEntities(SwatString::condense(
					SwatString::stripXHTMLTags($this->article->bodytext, 400)));
			} else {
				$this->layout->data->meta_description =
					SwatString::minimizeEntities($this->article->description);
			}
		}
	}

	// }}}
	// {{{ protected function buildContent()

	protected function buildContent()
	{
		if ($this->article instanceof SiteArticle) {
			$this->layout->startCapture('content');
			$this->displayArticle($this->article);
			$this->displaySubArticles($this->article->getVisibleSubArticles());
			$this->layout->endCapture();
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		if ($this->path instanceof SitePath) {
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

	// finalize phase
	// {{{ public function finalize()

	/**
	 * Always runs after {@link SitePage::build()} and before
	 * {@link SiteLayout::complete()}. This method is intended to add HTML head
	 * entries or perform other actions that should happen after the page has
	 * been built.
	 */
	public function finalize()
	{
	}

	// }}}
}

?>
