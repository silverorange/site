<?php

require_once 'Site/SiteSearchIndexer.php';
require_once 'Site/Site.php';
require_once 'NateGoSearch/NateGoSearchIndexer.php';
require_once 'NateGoSearch/NateGoSearchPSpellChecker.php';

/**
 * Site search indexer application for NateGoSearch
 *
 * This indexer indexes articles by default.
 * Subclasses may change how and what gets indexed.
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteNateGoSearchIndexer extends SiteSearchIndexer
{
	// {{{ class constants

	/**
	 * Verbosity level for showing nothing.
	 */
	const VERBOSITY_NONE = 0;

	/**
	 * Verbosity level for showing all indexing actions
	 */
	const VERBOSITY_ALL = 1;

	// }}}
	// {{{ protected properties

	/**
	 * Whether or not the search cache should be cleared after indexing
	 *
	 * @see SiteNateGoSearchIndexer::checkQueue()
	 */
	protected $clear_cache = false;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $title, $documentation)
	{
		parent::__construct($id, $title, $documentation);

		$verbosity = new SiteCommandLineArgument(array('-v', '--verbose'),
			'setVerbosity', 'Sets the level of verbosity of the indexer. '.
			'Pass 0 to turn off all output.');

		$verbosity->addParameter('integer',
			'--verbose expects a level between 0 and 1.',
			self::VERBOSITY_ALL);

		$all = new SiteCommandLineArgument(array('-A', '--all'),
			'queue', 'Indexes all content rather than just queued '.
			'content.');

		$this->addCommandLineArgument($verbosity);
		$this->addCommandLineArgument($all);
	}

	// }}}
	// {{{ public function queue()

	/**
	 * Repopulates the entire search queue
	 */
	public function queue()
	{
		$this->queueArticles();
	}

	// }}}
	// {{{ public function run()

	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();
		$this->checkQueue();
		$this->index();
		$this->clearCache();
	}

	// }}}
	// {{{ protected function checkQueue()

	/**
	 * Checks to see if the search queue has any entries
	 *
	 * If the queue has entries, cached search results are cleared at the end
	 * of teh idexing process.
	 */
	protected function checkQueue()
	{
		$sql = 'select count(document_id) from NateGoSearchQueue';
		$count = SwatDB::queryOne($this->db, $sql);
		if ($count == 0) {
			$this->output(Site::_('No entries in the search queue.')."\n",
				self::VERBOSITY_ALL);
		} else {
			$this->output(Site::_('Search queue has entries. Cached search '.
				'results will be cleared after indexing is complete.')."\n",
				self::VERBOSITY_ALL);

			$this->clear_cache = true;
		}
	}

	// }}}
	// {{{ protected function index()

	/**
	 * Indexes documents
	 *
	 * Subclasses should override this method to add or remove additional
	 * indexed tables.
	 */
	protected function index()
	{
		$this->indexArticles();
	}

	// }}}
	// {{{ protected function clearCache()

	/**
	 * Clears cached search results
	 */
	protected function clearCache()
	{
		if ($this->clear_cache) {
			$this->output(Site::_('Clearing cached search results ... '),
				self::VERBOSITY_ALL);

			$sql = 'delete from NateGoSearchCache';
			SwatDB::exec($this->db, $sql);

			$this->output(Site::_('done')."\n",
				self::VERBOSITY_ALL);
		}
	}

	// }}}
	// {{{ protected function queueArticles()

	/**
	 * Repopulates the articles queue
	 */
	protected function queueArticles()
	{
		$this->output(Site::_('Repopulating article search queue ... '),
			self::VERBOSITY_ALL);

		$type = NateGoSearch::getDocumentType($this->db, 'article');

		// clear queue
		$sql = sprintf('delete from NateGoSearchQueue
			where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		// fill queue
		$sql = sprintf('insert into NateGoSearchQueue
			(document_type, document_id) select %s, id from Article',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		$this->output(Site::_('done')."\n", self::VERBOSITY_ALL);
	}

	// }}}
	// {{{ protected function indexArticles()

	/**
	 * Indexes articles
	 *
	 * Articles are visible if they are enabled. Articles may not be shown in
	 * the menu but are still visible. Articles also have an explicit
	 * searchable field.
	 */
	protected function indexArticles()
	{
		$spell_checker = new NateGoSearchPSpellSpellChecker('en');
		$spell_checker->setCustomWordList($this->getCustomWordList());
		$spell_checker->loadCustomContent();

		$indexer = new NateGoSearchIndexer('article', $this->db);

		$indexer->setSpellChecker($spell_checker);
		$indexer->addTerm(new NateGoSearchTerm('title', 5));
		$indexer->addTerm(new NateGoSearchTerm('bodytext'));
		$indexer->setMaximumWordLength(32);
		$indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		$type = NateGoSearch::getDocumentType($this->db, 'article');

		$sql = sprintf('select id, shortname, title, bodytext from Article
			inner join NateGoSearchQueue
				on Article.id = NateGoSearchQueue.document_id
				and NateGoSearchQueue.document_type = %s',
			$this->db->quote($type, 'integer'));

		$this->output(Site::_('Indexing articles ... ').'   ',
			self::VERBOSITY_ALL);

		$articles = SwatDB::query($this->db, $sql);
		$total = count($articles);
		$count = 0;
		foreach ($articles as $article) {

			if ($count % 10 == 0) {
				$indexer->commit();
				$this->output(str_repeat(chr(8), 3), self::VERBOSITY_ALL);
				$this->output(sprintf('%2d%%', ($count / $total) * 100),
					self::VERBOSITY_ALL);
			}

			$document = new NateGoSearchDocument($article, 'id');
			$indexer->index($document);

			$count++;
		}

		$this->output(str_repeat(chr(8), 3).Site::_('done')."\n",
			self::VERBOSITY_ALL);

		$indexer->commit();
		unset($indexer);

		$sql = sprintf('delete from NateGoSearchQueue where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
	// {{{ protected function getCustomWordlist()

	/**
	 * Get the custom word list
	 *
	 * Get the custom word list that is used by this fulltext search engine.
	 *
	 * @return string the path to the custom word list
	 */
	protected function getCustomWordList()
	{
		return './custom-wordlist.pws';
	}

	// }}}
}

?>
