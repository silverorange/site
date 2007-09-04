<?php

require_once 'Swat/SwatExceptionLogger.php';
require_once 'Swat/exceptions/SwatException.php';
require_once 'Site/exceptions/SiteNotFoundException.php';

/**
 * An exception logger that creates HTML files containing exception details
 * and puts a link in the system error log to the details file
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
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
		// don't bother logging exceptions due to 404s
		if ($e instanceof SiteNotFoundException)
			return;

		$hash = md5(uniqid());
		$log_directory = date('Y-m-d');
		$log_filename = 'exception-'.$hash.'.html';
		$log_directory_path = $this->log_location.'/'.$log_directory;
		$log_filepath = $log_directory_path.'/'.$log_filename;

		if (!file_exists($log_directory_path)) {
			mkdir($log_directory_path);
			chmod($log_directory_path, 0770);
		}

		if (($log_file = fopen($log_filepath, 'w')) !== false) {
			fwrite($log_file, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 '.
				'Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">');

			fwrite($log_file, '<html xmlns="http://www.w3.org/1999/xhtml" '.
				'xml:lang="en" lang="en">');

			fwrite($log_file, '<head><meta http-equiv="Content-Type" '.
				'content="text/html; charset=UTF-8" /></head><body>');

			fwrite($log_file, '<table>');
			fwrite($log_file, '<tr><th>Exeception Time:</th><td>'.
				date('c', time()).'</td></tr>');

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
			fwrite($log_file, '</body></html>');
			fclose($log_file);
		}

		if ($this->base_uri === null)
			$summary = $e->getClass().': '.$log_filepath;
		else
			$summary = $e->getClass().': '.$this->base_uri.
				'/'.$log_directory.'/'.$log_filename;

		error_log($summary, 0);
	}
}

?>
