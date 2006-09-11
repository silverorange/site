<?php

require_once 'Site/exceptions/SiteException.php';
require_once 'Site/SiteApplicationModule.php';
require_once 'SwatDB/exceptions/SwatDBException.php';
require_once 'MDB2.php';

/**
 * Application module for database connectivity
 *
 * @package   Site
 * @copyright 2005-2006 silverorange
 */
class SiteDatabaseModule extends SiteApplicationModule
{
    // {{{ public properties
	
	/**
	 * DSN of database
	 *
	 * A DSN string specifying the database to connect to.  Set this before
	 * calling {@link SiteApplication::init()}, afterwords consider it
	 * readonly.
	 *
	 * @var string
	 */
	public $dsn;

	// }}}
	// {{{ protected properties

	/**
	 * The database object
	 *
	 * @var MDB2_Connection database connection object. This property is 
	 *                       readonly publically accessible as 'mdb2'.
	 */
	protected $connection = null;

    // }}}
    // {{{ public function init()

	public function init()
	{
		$this->connection = MDB2::connect($this->dsn);

		if (MDB2::isError($this->connection))
			throw new SwatDBException($this->connection);

		$this->connection->options['portability'] =
			$this->connection->options['portability'] ^
				MDB2_PORTABILITY_EMPTY_TO_NULL;

		// Set up convenience reference
		$this->app->db = $this->getConnection();
	}

    // }}}
	// {{{ public function getConnection()

	/**
	 * Retrieve the MDB2 connection object
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	// }}}
}

?>
