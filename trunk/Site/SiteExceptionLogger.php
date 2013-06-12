<?php

require_once 'Swat/SwatExceptionLogger.php';
require_once 'Swat/exceptions/SwatException.php';
require_once 'Site/exceptions/SiteNotFoundException.php';

/**
 * An exception logger that creates HTML files containing exception details
 * and puts a link in the system error log to the details file
 *
 * @package   Site
 * @copyright 2006-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteExceptionLogger extends SwatExceptionLogger
{
	// {{{ protected properties

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
	 * Unix group to use when creating new dirs and files
	 *
	 * If null, the current group is used.
	 *
	 * @var string
	 */
	protected $unix_group;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new exception loggger
	 *
	 * @param string $log_location the location in which to store detailed
	 *                              error log files.
	 * @param string $base_uri optional.
	 * @param integer $unix_group optional.
	 */
	public function __construct($log_location, $base_uri = null,
		$unix_group = null)
	{
		$this->log_location = $log_location;
		$this->base_uri     = $base_uri;
		$this->unix_group   = $unix_group;
	}

	// }}}
	// {{{ public function log()

	/**
	 * Logs an exception
	 */
	public function log(SwatException $e)
	{
		// don't bother logging exceptions due to 404s
		if ($e instanceof SiteNotFoundException)
			return;

		$id           = md5(uniqid());
		$directory    = date('Y-m-d');
		$log_path     = $this->getLogPath($directory);
		$log_filepath = $this->getLogFilePath($directory, $id);

		// create path if it does not exist
		if (!file_exists($log_path)) {
			mkdir($log_path, 0770, true);
			chmod($log_path, 0770);

			if ($this->unix_group !== null)
				chgrp($log_path, $this->unix_group);
		}

		if (($log_file = fopen($log_filepath, 'w')) !== false) {
			fwrite($log_file, $this->getBody($e));
			fclose($log_file);

			if ($this->unix_group !== null)
				chgrp($log_filepath, $this->unix_group);
		}

		// add to syslog
		$this->logSummary($this->getSummary($e, $directory, $id));
	}

	// }}}
	// {{{ protected function logSummary()

	protected function logSummary($summary)
	{
		error_log($summary, 0);
	}

	// }}}
	// {{{ protected function getLogPath()

	protected function getLogPath($directory)
	{
		return $this->log_location.'/'.$directory;
	}

	// }}}
	// {{{ protected function getLogFilename()

	protected function getLogFilename($id)
	{
		return 'exception-'.$id.'.html';
	}

	// }}}
	// {{{ protected function getLogFilePath()

	protected function getLogFilePath($directory, $id)
	{
		return $this->getLogPath($directory).'/'.$this->getLogFilename($id);
	}

	// }}}
	// {{{ protected function getSummary()

	protected function getSummary(SwatException $e, $directory, $id)
	{
		if ($this->base_uri === null) {
			$summary = $e->getClass().': '.
				$this->getLogFilePath($directory, $id);
		} else {
			$summary = $e->getClass().': '.$this->base_uri.'/'.
				$directory.'/'.$this->getLogFilename($id);
		}

		return $summary;
	}

	// }}}
	// {{{ protected function getBody()

	protected function getBody(SwatException $e)
	{
		ob_start();

		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 ',
			'Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
			"\n";

		echo '<html xmlns="http://www.w3.org/1999/xhtml" ',
			'xml:lang="en" lang="en">', "\n";

		echo '<head><meta http-equiv="Content-Type" '.
			'content="text/html; charset=UTF-8" /></head><body>', "\n";

		echo '<table>', "\n";

		echo '<tr><th>Exception Time:</th><td>', date('c', time()),
			'</td></tr>', "\n";

		if (isset($_SERVER['HTTP_HOST'])) {
			echo '<tr><th>HTTP Host:</th><td>', $_SERVER['HTTP_HOST'],
				'</td></tr>', "\n";
		}

		if (isset($_SERVER['REQUEST_URI'])) {
			echo '<tr><th>Request URI:</th><td>', $_SERVER['REQUEST_URI'],
				'</td></tr>', "\n";
		}

		if (isset($_SERVER['REQUEST_TIME'])) {
			echo '<tr><th>Request Time:</th><td>',
				date('c', $_SERVER['REQUEST_TIME']), '</td></tr>', "\n";
		}

		if (isset($_SERVER['HTTP_REFERER'])) {
			echo '<tr><th>HTTP Referer:</th><td>', $_SERVER['HTTP_REFERER'],
				'</td></tr>', "\n";
		}

		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			echo '<tr><th>HTTP User Agent:</th><td>',
				$_SERVER['HTTP_USER_AGENT'], '</td></tr>', "\n";
		}

		$remote_ip = null;

		if (isset($_SERVER['HTTP_X_FORWARDED_IP'])) {
			$remote_ip = $_SERVER['HTTP_X_FORWARDED_IP'];
		} elseif (isset($_SERVER['REMOTE_ADDR'])) {
			$remote_ip = $_SERVER['REMOTE_ADDR'];
		}

		if ($remote_ip !== null) {
			echo '<tr><th>Remote Address:</th><td>', $remote_ip,
				'</td></tr>', "\n";
		}

		echo '</table>', "\n";

		echo $e->toXHTML();

		echo '</body></html>', "\n";

		return ob_get_clean();
	}

	// }}}
}

?>
