<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'SwatDB/exceptions/SwatDBException.php';
require_once 'MDB2.php';

/**
 * Application module for database connectivity
 *
 * @package   Site
 * @copyright 2004-2006 silverorange
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

	/**
	 * The database object
	 *
	 * @var MDB2_Connection Database connection object (readonly)
	 */
	public $mdb2 = null;

    // }}}
    // {{{ public function init()

	public function init()
	{
		// TODO: change to array /form of DSN and move parts to a secure include file.
		$dsn = 'pgsql://php:test@zest/'.$this->name;
		$this->mdb2 = MDB2::connect($dsn);
		$this->mdb2->options['debug'] = true;

		if (MDB2::isError($this->mdb2))
			throw new SwatDBException($this->mdb2);
	}

    // }}}
}

?>
