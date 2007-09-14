<?php

require_once 'Site/pages/SiteArticlePage.php';
require_once 'Site/SiteNateGoFulltextSearchEngine.php';
require_once 'Site/SiteArticleSearchEngine.php';
require_once 'Swat/SwatUI.php';

require_once 'Swat/SwatNavBar.php';
require_once 'Swat/SwatString.php';

/**
 * Page for displaying search results
 *
 * By default this page displays search results for articles but it can
 * be sub-classed to display results for other types of content as well.
 *
 * @package   Site
 * @copyright 2007 silverorange
 */
class SiteSearchResultsPage extends SiteArticlePage
{
	// {{{ protected properties

	/**
	 * The user-interface to display results in
	 *
	 * @var SwatUI
	 */
	protected $ui;

	/**
	 * The SwatML file to load the search results user-interface from
	 *
	 * @var string
	 */
	protected $ui_xml = 'Site/pages/search-results.xml';

	/**
	 * The fulltext result object
	 *
	 * @var SiteFulltextResult
	 */
	protected $fulltext_result;

	/**
	 * Array to track which content types have results
	 *
	 * Array values are content type strings such as 'article'.
	 *
	 * @var array
	 */
	protected $has_results = array();

	// }}}
	// {{{ private properties

	private $search_data_fields = array();
	private $search_data_values;

	// }}}
	// {{{ protected function addSearchDataField()

	/**
	 * Registers a search data field
	 *
	 * Defines the name of a search data name/value pair.  A GET variable
	 * by this name will be looked for and if found its value will be stored
	 * in this search data field.
	 *
	 * @param string $name the name of the search data field.
	 */
	protected function addSearchDataField($name)
	{
		$this->search_data_fields[] = $name;
	}

	// }}}
	// {{{ protected function hasSearchDataValue()

	/**
	 * Whether a search data field has a value
	 *
	 * @param string $name the name of the search data field to check for a
	 *                      value.
	 *
	 * @return boolean whether the field contains a value.
	 */
	protected function hasSearchDataValue($name)
	{
		return isset($this->search_data_values[$name]);
	}

	// }}}
	// {{{ protected function getSearchDataValue()

	/**
	 * Retrieve the value of a search data field
	 *
	 * @param string $name the name of the search data field to retrieve a
	 *                      value from.
	 *
	 * @return string the value of the field.
	 */
	protected function getSearchDataValue($name)
	{
		$value = null;

		if ($this->hasSearchDataValue($name))
			$value = $this->search_data_values[$name];

		return $value;
	}

	// }}}
	// {{{ protected function getSearchDataValues()

	/**
	 * Retrieve the values of all search data fields
	 *
	 * @return array the values of the fields in the form $name => $value.
	 */
	protected function getSearchDataValues()
	{
		return $this->search_data_values;
	}

	// }}}

	// init phase
	// {{{ public function init

	public function init()
	{
		parent::init();

		$this->addSearchDataField('keywords');
		$this->initSearchData();

		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->ui_xml);
		$this->ui->init();
	}

	// }}}
	// {{{ private function initSearchData

	private function initSearchData()
	{
		$search_data_values = array();

		foreach ($this->search_data_fields as $field) {
			$value = SiteApplication::initVar($field,
					null, SiteApplication::VAR_GET);

			if ($value !== null && strlen($value) > 0)
				$this->search_data_values[$field] = $value;
		}
	}

	// }}}

	// process phase
	// {{{ public function process

	public function process()
	{
		parent::process();

		$this->ui->process();
	}

	// }}}
	
	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		if (count($this->getSearchDataValues()) > 0) {
			$this->buildResults();
			$this->buildMessages();
		}

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildResults()

	protected function buildResults()
	{
		$fulltext_result = $this->searchFulltext();
		$this->buildArticles($fulltext_result);

		if ($fulltext_result !== null)
			$this->buildMisspellings($fulltext_result);
	}

	// }}}
	// {{{ protected function buildMisspellings()

	/**
	 * Build suggested spelling message
	 *
	 * @param SiteFullTextResult $fulltext_result a fulltext result object
	 */
	protected function buildMisspellings($fulltext_result)
	{
		$misspellings = $fulltext_result->getMisspellings();

		if (count($misspellings) > 0 ) {
			$corrected_phrase = ' '.$this->getSearchDataValue('keywords').' ';
			$corrected_string = SwatString::minimizeEntities($corrected_phrase);

			foreach ($misspellings as $misspelling => $correction) {
				// for URL
				$corrected_phrase = str_replace(' '.$misspelling.' ',
					' '.$correction.' ', $corrected_phrase);

				// for display
				$corrected_string = str_replace(
					' '.SwatString::minimizeEntities($misspelling).' ',
					' <strong>'.SwatString::minimizeEntities($correction).
					'</strong> ',
					$corrected_string);
			}

			$corrected_phrase = trim($corrected_phrase);
			$corrected_string = trim($corrected_string);

			$misspellings_link = new SwatHtmlTag('a');
			$misspellings_link->href = sprintf('search?keywords=%s',
				urlencode($corrected_phrase));

			$misspellings_link->setContent($corrected_string, 'text/xml');

			$misspellings_message = new SwatMessage(sprintf(
				Site::_('Did you mean “%s”?'),
				$misspellings_link->toString()));

			$misspellings_message->content_type = 'text/xml';

			$messages = $this->ui->getWidget('results_message');
			$messages->add($misspellings_message);
		}
	}

	// }}}
	// {{{ protected function buildMessages()

	protected function buildMessages()
	{
		if (count($this->has_results) == 0) {
			$message = $this->getNoResultsMessage();
			$messages = $this->ui->getWidget('results_message');
			$messages->add($message);
		}
	}

	// }}}
	// {{{ protected function getNoResultsMessage()

	/**
	 * Get the no-results message for this search page
	 *
	 * @return SwatMessage the no-results message.
	 */
	protected function getNoResultsMessage()
	{
		if ($this->hasSearchDataValue('keywords')) {
			$keywords = $this->getSearchDataValue('keywords');
			$title = sprintf(Site::_('No results found for “%s”.'),
				SwatString::minimizeEntities($keywords));
		} else {
			$title = Site::_('No results found.');
		}

		$message = new SwatMessage($title);

		ob_start();
		echo '<ul>';

		foreach ($this->getSearchTips() as $tip)
			printf('<li>%s</li>', $tip);

		printf('<li>%s<ul>', Site::_('Your search:'));

		foreach ($this->getSearchSummary() as $summary)
			printf('<li>%s</li>', $summary);

		echo '</ul></li></ul>';
		$message->secondary_content = ob_get_clean();
		$message->content_type = 'text/xml';

		return $message;
	}

	// }}}
	// {{{ protected function getSearchTips()

	/**
	 * Get array of search tips to display when there are no results
	 *
	 * @return array an array of tip strings.
	 */
	protected function getSearchTips()
	{
		$tips = array();

		if ($this->hasSearchDataValue('keywords'))
			$tips[] = Site::_('Try using less specific keywords');
		else
			$tips[] = Site::_('Try broadening your search');

		return $tips;
	}

	// }}}
	// {{{ protected function getSearchSummary()

	/**
	 * Get a summary of the criteria that was used to perform the search
	 *
	 * @return array an array of summary strings.
	 */
	protected function getSearchSummary()
	{
		$summary = array();

		if ($this->hasSearchDataValue('keywords')) {
			$keywords = $this->getSearchDataValue('keywords');
			$summary[] = sprintf('Keywords: <b>%s</b>',
				SwatString::minimizeEntities($keywords));
		}

		return $summary;
	}

	// }}}
	// {{{ protected function searchFulltext()

	/**
	 * Perform a fulltext search and return the results
	 *
	 * @return SiteFulltextSearchResult the result or null if no search was
	 *                                   performed.
	 */
	protected function searchFulltext()
	{
		$fulltext_result = null;

		if ($this->hasSearchDataValue('keywords')) {
			$keywords = $this->getSearchDataValue('keywords');

			$fulltext_engine =
				new SiteNateGoFulltextSearchEngine($this->app->db);

			$types = $this->getFulltextTypes();
			$fulltext_engine->setTypes($types);
			$fulltext_result = $fulltext_engine->search($keywords);
		}

		return $fulltext_result;
	}

	// }}}
	// {{{ protected function getFulltextTypes()

	/**
	 * Retrieve the content types to use when performing a fulltext search
	 *
	 * @return array array of content type string.
	 */
	protected function getFulltextTypes()
	{
		$types = array();
		$types[] = 'article';

		return $types;
	}

	// }}}
	// {{{ protected function buildArticles()

	/**
	 * Build article search results
	 *
	 * @param SiteFulltextResult $fulltext_result Optional fulltext result to
	 *                                             pass to article search engine.
	 */
	protected function buildArticles($fulltext_result)
	{
		$pager = $this->ui->getWidget('article_pager');
		$engine = $this->instantiateArticleSearchEngine();
		$engine->setFulltextResult($fulltext_result);
		$articles = $engine->search($pager->page_size, $pager->current_record);

		$pager->total_records = $engine->getResultCount();
		$pager->link = $this->source;

		if (count($articles) > 0) {
			$this->has_results[] = 'article';

			$frame = $this->ui->getWidget('article_results_frame');
			$results = $this->ui->getWidget('article_results');
			$frame->visible = true;

			ob_start();
			$this->displayArticles($articles);
			$results->content = ob_get_clean();
		}
	}

	// }}}
	// {{{ protected function instantiateArticleSearchEngine()

	/**
	 * Retrieve a new article search engine
	 *
	 * @return SiteArticleSearchEngine the search engine.
	 */
	protected function instantiateArticleSearchEngine()
	{
		$engine = new SiteArticleSearchEngine($this->app);

		return $engine;
	}

	// }}}
	// {{{ protected function displayArticles()

	/**
	 * Display search results for a collection of articles 
	 *
	 * @param SiteArticleWrapper $articles the articles to display search
	 *                                      results for.
	 */
	protected function displayArticles(SiteArticleWrapper $articles)
	{
		echo '<ul class="search-results">';
		$paragraph_tag = new SwatHtmlTag('p');

		foreach ($articles as $article) {
			$navbar = new SwatNavBar();
			$navbar->addEntries($article->getNavBarEntries());

			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = $navbar->getLastEntry()->link;
			$anchor_tag->class = 'search-result-title';

			echo '<li>';
			$anchor_tag->setContent($article->title);
			$anchor_tag->display();
			$navbar->display();
			$paragraph_tag->open();
			echo SwatString::condense($article->bodytext, 150).'&nbsp;';
			$anchor_tag->setContent(Site::_('more').'&nbsp;»');
			$anchor_tag->display();
			$paragraph_tag->close();
			echo '</li>';
		}

		echo '</ul>';
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/site/styles/site-search-results-page.css', Site::PACKAGE_ID));
	}

	// }}}
}

?>
