<?php

require_once 'Swat/SwatError.php';

/**
 * An error in Site
 *
 * @package   Site
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteError extends SwatError
{
	// {{{ public function log()

	/**
	 * Logs this error 
	 *
	 * The error is logged to the webserver error log.
	 */
	public function log()
	{
		parent::log();
	}

	// }}}
	// {{{ public static function handle()

	/**
	 * Handles an error 
	 *
	 * When an error occurs, a SiteError object is created and processed.
	 *
	 * @param integer $errno the severity code of the handled error.
	 * @param string $errstr the message of the handled error.
	 * @param string $errfile the file ther handled error occurred in.
	 * @param integer $errline the line the handled error occurred at.
	 */
	public static function handle($errno, $errstr, $errfile, $errline)
	{
		// only handle error if error reporting is not suppressed
		if (ini_get('error_reporting') != 0) {
			$error = new SiteError($errno, $errstr, $errfile, $errline);
			$error->process();
		}
	}

	// }}}
	// {{{ public static function setupHandler()

	/**
	 * Set the PHP error handler to use SiteError
	 */
	public static function setupHandler()
	{
		/*
		 * All run-time errors except for forwards compatibility
		 * (E_STRICT) are handled by default.
		 */
		set_error_handler(array('SiteError', 'handle'),
			E_USER_ERROR | E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE);
	}

	// }}}
}

?>
