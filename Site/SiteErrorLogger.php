<?php

require_once 'Swat/SwatErrorLogger.php';

/**
 * An error logger that creates HTML files containing error details and puts a
 * link in the system error log to the details file
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteErrorLogger extends SwatErrorLogger
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
	 * Creates a new error loggger
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
	 * Logs an error
	 */
	public function log(SwatError $e)
	{
		$hash = md5(uniqid());
		$log_directory = date('Y-m-d');
		$log_filename = 'error-'.$hash.'.html';
		$log_directory_path = $this->log_location.'/'.$log_directory;
		$log_filepath = $log_directory_path.'/'.$log_filename;

		if (!file_exists($log_directory_path)) {
			mkdir($log_directory_path);
			chmod($log_directory_path, 0770);
		}

		if (($log_file = fopen($log_filepath, 'w')) !== false) {
			fwrite($log_file, '<table>');
			fwrite($log_file, '<tr><th>Error Time:</th><td>'.
				date('c', time()).'</td></tr>');

			if (isset($_SERVER['HTTP_HOST']))
				fwrite($log_file, '<tr><th>HTTP Host:</th><td>'.
					$_SERVER['HTTP_HOST'].'</td></tr>');

			if (isset($_SERVER['REQUEST_URI']))
				fwrite($log_file, '<tr><th>Request URI:</th><td>'.
					$_SERVER['REQUEST_URI'].'</td></tr>');

			if (isset($_SERVER['REQUEST_TIME']))
				fwrite($log_file, '<tr><th>Request Time:</th><td>'.
					date('c', $_SERVER['REQUEST_TIME']).'</td></tr>');

			if (isset($_SERVER['HTTP_REFERER']))
				fwrite($log_file, '<tr><th>HTTP Referer:</th><td>'.
					$_SERVER['HTTP_REFERER'].'</td></tr>');

			if (isset($_SERVER['HTTP_USER_AGENT']))
				fwrite($log_file, '<tr><th>HTTP User Agent:</th><td>'.
					$_SERVER['HTTP_USER_AGENT'].'</td></tr>');

			if (isset($_SERVER['REMOTE_ADDR']))
				fwrite($log_file, '<tr><th>Remote Address:</th><td>'.
					$_SERVER['REMOTE_ADDR'].'</td></tr>');

			fwrite($log_file, '</table>');
			fwrite($log_file, $e->toXHTML());
			fclose($log_file);
		}

		if ($this->base_uri === null)
			$summary = $e->getSummary().': '.$log_filepath;
		else
			$summary = $e->getSummary().': '.
				$this->base_uri.'/'.$log_directory.'/'.$log_filename;

		$this->logSummary($summary);
	}

	protected function logSummary($summary)
	{
		error_log($summary, 0);
	}
}

?>
