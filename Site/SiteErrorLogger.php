<?php

require_once 'Swat/SwatErrorLogger.php';

/**
 * An error logger that creates HTML files containing error details and puts a
 * link in the system error log to the details file
 *
 * @package   Site
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteErrorLogger extends SwatErrorLogger
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
	 * Unix group to use when creating new dirs
	 *
	 * If null, the current group is used.
	 *
	 * @var string
	 */
	protected $unix_group;

	/**
	 * Error identifier
	 *
	 * Unique id used for filenames and paths. Generated once for each error.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Directory in which logged errors will be stored
	 *
	 * Not necessarily unique. Generated once for each error.
	 *
	 * @var string
	 */
	protected $directory;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new error logger
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
		$this->id           = md5(uniqid());
		$this->directory    = date('Y-m-d');
	}

	// }}}
	// {{{ public function log()

	/**
	 * Logs an error
	 */
	public function log(SwatError $e)
	{
		$log_path     = $this->getLogPath();
		$log_filepath = $this->getLogFilePath();

		// create path if it does not exist
		if (!file_exists($log_path)) {
			mkdir($log_path, 0770, true);
			chmod($log_path, 0770);

			if ($this->unix_group !== null)
				chgrp($log_path, $this->unix_group);
		}

		// save detailed error log
		if (($log_file = fopen($log_filepath, 'w')) !== false) {
			fwrite($log_file, $this->getBody($e));
			fclose($log_file);

			if ($this->unix_group !== null)
				chgrp($log_filepath, $this->unix_group);
		}

		// add to syslog
		$this->logSummary($this->getSummary($e));
	}

	// }}}
	// {{{ protected function logSummary()

	protected function logSummary($summary)
	{
		error_log($summary, 0);
	}

	// }}}
	// {{{ protected function getLogPath()

	protected function getLogPath()
	{
		return $this->log_location.'/'.$this->directory;
	}

	// }}}
	// {{{ protected function getLogFilename()

	protected function getLogFilename()
	{
		return 'error-'.$this->id.'.html';
	}

	// }}}
	// {{{ protected function getLogFilePath()

	protected function getLogFilePath()
	{
		return $this->getLogPath().'/'.$this->getLogFilename();
	}

	// }}}
	// {{{ protected function getSummary()

	protected function getSummary(SwatError $e)
	{
		if ($this->base_uri === null) {
			$summary = $e->getSummary().': '.$this->getLogFilePath();
		} else {
			$summary = $e->getSummary().': '.$this->base_uri.'/'.
				$this->directory.'/'.$this->getLogFilename();
		}

		return $summary;
	}

	// }}}
	// {{{ protected function getBody()

	protected function getBody(SwatError $e)
	{
		ob_start();

		echo '<table>', "\n";

		echo '<tr><th>Error Time:</th><td>', date('c', time()),
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

		if (isset($_SERVER['REMOTE_ADDR'])) {
			echo '<tr><th>Remote Address:</th><td>', $_SERVER['REMOTE_ADDR'],
				'</td></tr>', "\n";
		}

		echo '</table>', "\n";

		echo $e->toXHTML();

		return ob_get_clean();
	}

	// }}}
}

?>
