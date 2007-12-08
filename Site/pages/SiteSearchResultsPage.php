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
	private $search_engines = array();

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
	 * @param boolean $is_array whether to expect array values in this field.
	 */
	protected function addSearchDataField($name, $is_array = false)
	{
		$this->search_data_fields[$name] = $is_array;
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
	// {{{ protected function getQueryString()

	/**
	 * Retrieve a query string containing all search data fields
	 *
	 * @param mixed $exclude name of the search data field to exclude from the
	 *                        returned string or an array of names to exclude.
	 *
	 * @return string the query sting.
	 */
	protected function getQueryString($exclude = null)
	{
		$string = '';

		$first = true;
		foreach ($this->getSearchDataValues() as $name => $value) {
			if ($exclude !== null) {
				if (!is_array($exclude))
					$exclude = array($exclude);

				if (in_array($name, $exclude))
					continue;
			}

			if ($first)
				$first = false;
			else
				$string.= '&';

			if (is_array($value)) {
				$name.= '[]';
				foreach ($value as $subvalue)
					$string.= sprintf('%s=%s', $name, $subvalue);
			} else {
				$string.= sprintf('%s=%s', $name, $value);
			}
		}

		return $string;
	}

	// }}}
	// {{{ protected function setSearchEngine()

	protected function setSearchEngine($name, $engine)
	{
		$this->search_engines[$name] = $engine;
	}

	// }}}
	// {{{ protected function hasSearchEngine()

	protected function hasSearchEngine($name)
	{
		return isset($this->search_engines[$name]);
	}

	// }}}
	// {{{ protected function getSearchEngine()

	protected function getSearchEngine($name)
	{
		if (isset($this->search_engines[$name]))
			return $this->search_engines[$name];
		else
			throw new SiteException('Search engine does not exist.');
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

		foreach ($this->search_data_fields as $field => $is_array) {
			$value = SiteApplication::initVar($field,
					null, SiteApplication::VAR_GET);

			if ($value !== null) {
				if ($is_array) {
					if (is_array($value)) {
						$this->search_data_values[$field] = $value;
					} elseif (strlen($value) > 0) {
						// got string, make into an array
						$this->search_data_values[$field] = array($value);
					}
				} else {
					if (strlen($value) > 0)
						$this->search_data_values[$field] = $value;
				}
			}
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

		$this->buildResults();
		$this->buildMessages();

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildResults()

	protected function buildResults()
	{
		if (count($this->getSearchDataValues()) > 0) {
			$fulltext_result = $this->searchFulltext();
			$this->buildArticles($fulltext_result);

			if ($fulltext_result !== null)
				$this->buildMisspellings($fulltext_result);
		}
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
				$corrected_phrase = str_ireplace(' '.$misspelling.' ',
					' '.$correction.' ', $corrected_phrase);

				// for display
				$corrected_string = str_ireplace(
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

			$query_string = $this->getQueryString('keywords');
			if (strlen($query_string) > 0)
				$misspellings_link->href.= '&'.$query_string;

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

		if ($this->hasSearchEngine('article')) {
			$engine = $this->getSearchEngine('article');
			$summary = $engine->getSearchSummary();
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

			// TODO: remove refernce to StoreApplication::getLocale()
			$fulltext_engine = new SiteNateGoFulltextSearchEngine(
				$this->app->db, $this->app->getLocale());

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
		$this->setSearchEngine('article', $engine);

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
		echo '<ul class="site-search-results">';
		$paragraph_tag = new SwatHtmlTag('p');

		foreach ($articles as $article) {
			$navbar = new SwatNavBar();
			$navbar->addEntries($article->getNavBarEntries());

			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = $navbar->getLastEntry()->link;
			$anchor_tag->class = 'site-search-result-title';

			echo '<li>';
			$anchor_tag->setContent($article->title);
			$anchor_tag->display();
			$navbar->display();
			$paragraph_tag->open();

			if (strlen($article->description))
				echo SwatString::condense($article->description, 150).'&nbsp;';
			else
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
