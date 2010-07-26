<?php

require_once 'Swat/exceptions/SwatException.php';
require_once 'Swat/SwatError.php';
require_once 'Site/SiteErrorLogger.php';
require_once 'Site/SiteExceptionLogger.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Date/TimeZone.php';

/**
 * Common configuration module for command-line applications
 *
 * Contains a common configuration for command-line applications. This config
 * module is optional, but can be used to aid rapid development of command-line
 * apps.
 *
 * This module expects an application that provides:
 * - a database module
 *
 * This module configures:
 * - error and exception logging
 * - application database connection
 * - application time zone
 * - application locale
 *
 * @package   Site
 * @copyright 2007-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteCommandLineConfigModule extends SiteConfigModule
{
	// {{{ protected function configure()

	/**
	 * Configures modules of the application before they are initialized
	 */
	public function configure()
	{
		parent::configure();

		if (isset($this->exceptions->log_location))
			SwatException::setLogger(new SiteExceptionLogger(
				$this->exceptions->log_location, $this->exceptions->base_uri));

		if (isset($this->errors->log_location))
			SwatError::setLogger(new SiteErrorLogger(
				$this->errors->log_location, $this->errors->base_uri));

		$this->app->database->dsn = $this->database->dsn;

		$this->app->default_time_zone =
			new Date_TimeZone($this->date->time_zone);

		$this->app->default_locale = $this->i18n->locale;

		setlocale(LC_ALL, $this->i18n->locale.'.UTF-8');
	}

	// }}}
}

?>
