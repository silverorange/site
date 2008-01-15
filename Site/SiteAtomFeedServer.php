<?php

require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteConfigModule.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Abstract base class for serving an Atom feed
 *
 * @package   Site
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteAtomFeedServer extends SiteCommandLineApplication
{
	// {{{ public properties

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

	// }}}
	// {{{ public function run()

	/**
	 * Serves the Atom feed
	 */
	public function run()
	{
		$this->initModules();

		$this->db = $this->database->getConnection();

		$feed = $this->getFeed();

		$this->addEntries($feed);

		$feed->display();
	}

	// }}}
	// {{{ protected function getFeed()

	/**
	 * Instasiates an AtomFeed object
	 *
	 * @return AtomFeed
	 */
	protected function getFeed()
	{
		$feed = new AtomFeed();

		return $feed;
	}

	// }}}
	// {{{ protected abstract function addEntries()

	/**
	 * Abstract function to add atom entries to the feed
	 */
	protected abstract function addEntries($feed);

	// }}}
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this search indexer
	 *
	 * @return array the list of modules to load for this application.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'SiteConfigModule',
			'database' => 'SiteDatabaseModule',
		);
	}

	// }}}
}

?>
