<?php

require_once 'Site/SiteSearchIndexer.php';
require_once 'Site/Site.php';
require_once 'NateGoSearch/NateGoSearchIndexer.php';

/**
 * Site search indexer application for NateGoSearch
 *
 * This indexer indexed products, categories and articles by default.
 * Subclasses may change how and what gets indexed.
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteNateGoSearchIndexer extends SiteSearchIndexer
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
	// {{{ abstract public function queue()
	
	/**
	 * Repopulates the entire search queue
	 */
	abstract public function queue();

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
	// {{{ abstract protected function index()

	/**
	 * Indexes documents
	 *
	 * Subclasses should override this method to add or remove additional
	 * indexed tables.
	 */
	abstract protected function index();

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
}

?>
