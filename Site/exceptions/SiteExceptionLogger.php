<?php

require_once 'Swat/exceptions/SwatExceptionLogger.php';
require_once 'Swat/exceptions/SwatException.php';

/**
 * An exception logger that creates HTML files containing exception details
 * and puts a link in the system error log to the details file
 *
 * @package   Site
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteExceptionLogger extends SwatExceptionLogger
{
	/**
	 * Location in which to store detailed error logs
	 *
	 * This path should include a trailing slash.
	 *
	 * @var string
	 */
	protected $log_location;

	/**
	 * Creates a new exception loggger
	 *
	 * @param string $log_location the location in which to store detailed
	 *                              error log files.
	 */
	public function __construct($log_location)
	{
		$this->log_location = $log_location;
	}

	/**
	 * Logs an exception
	 */
	public function log(SwatException $e)
	{
		$hash = time();
		$log_filename = $this->log_location.'exception-'.$hash.'.html';

		if (($log_file = fopen($log_filename, 'w')) !== false) {
			fwrite($log_file, '<table>');
			fwrite($log_file,
				'<tr><th>Time:</th><td>'.date('c', time()).'</td></tr>');

			if (isset($_SERVER['REQUEST_URI']))
				fwrite($log_file, '<tr><th>Request URI:</th><td>'.
					$_SERVER['REQUEST_URI'].'</td></tr>');

			fwrite($log_file, '</table>');
			fwrite($log_file, $e->toXHTML());
			fclose($log_file);
		}
		$summary = $e->getClass().': '.$log_filename;
		error_log($summary, 0);
	}
}

?>
