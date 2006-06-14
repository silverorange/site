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
	 * Name of the database
	 *
	 * This is the name of the database to connect to.  Set this before calling
	 * {@link SiteApplication::init()}, afterwords consider it readonly.
	 *
	 * @var string
	 */
	public $name;

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
		// TODO: change to array /form of DSN and move parts to a secure include file.
		$dsn = 'pgsql://php:test@zest/'.$this->name;
		$this->connection = MDB2::connect($dsn);
		$this->connection->options['debug'] = true;

		if (MDB2::isError($this->connection))
			throw new SwatDBException($this->connection);

		// TODO: fixme
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
