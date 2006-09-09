<?php

require_once 'Site/SiteApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteConfigModule.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Abstract base class for a search indexer applications
 *
 * @package   Site
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteSearchIndexer extends SiteApplication
{
	// {{{ public properties

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

	// }}}
	// {{{ public function init()

	/**
	 * Initializes the modules of this application and sets up the database
	 * convenience reference
	 */
	public function init()
	{
		$this->initModules();
		$this->db = $this->database->getConnection();
	}

	// }}}
	// {{{ public abstract function index()

	/**
	 * Indexes search data intended to be indexed by this indexer
	 */
	public abstract function index();

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
