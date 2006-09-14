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
	 * Base URI to use to construct a link to the log file
	 *
	 * If null, the log file name is used instead.
	 *
	 * @var string
	 */
	protected $base_uri;

	/**
	 * Creates a new exception loggger
	 *
	 * @param string $log_location the location in which to store detailed
	 *                              error log files.
	 */
	public function __construct($log_location, $base_uri = null)
	{
		$this->log_location = $log_location;
		$this->base_uri = $base_uri;
	}

	/**
	 * Logs an exception
	 */
	public function log(SwatException $e)
	{
		$hash = time();
		$log_filename = 'exception-'.$hash.'.html';
		$log_filepath = $this->log_location.'/'.$log_filename;

		if (($log_file = fopen($log_filepath, 'w')) !== false) {
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

		if ($this->base_uri === null)
			$summary = $e->getClass().': '.$log_filepath;
		else
			$summary = $e->getClass().': '.$this->base_uri.'/'.$log_filename;

		error_log($summary, 0);
	}
}

?>
