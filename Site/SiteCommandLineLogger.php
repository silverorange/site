<?php

/**
 * PSR-3 compliant logger that sends messages to STDOUT and STDERR through
 * a Console_CommandLine_Outputter
 *
 * @package   Site
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteCommandLineLogger implements Psr\Log\LoggerInterface
{


	/**
	 * Show levels emergency, alert, critical.
	 */
	const LEVEL_NONE = 0;

	/**
	 * Show levels emergency, alert, critical, error, warning.
	 */
	const LEVEL_ERRORS = 1;

	/**
	 * Show information messages.
	 */
	const LEVEL_INFO = 2;

	/**
	 * Show detailed debugging information.
	 */
	const LEVEL_DEBUG = 3;




	/**
	 * Current logging level.
	 *
	 * @var integer
	 *
	 * @see SiteCommandLineLogger::setLevel()
	 */
	protected $level = self::LEVEL_NONE;

	/**
	 * Outputter object that display log messages
	 *
	 * @var Console_CommandLine_Outputter
	 */
	protected $out;




	/**
	 * Creates a new command-line logger
	 *
	 * @param Console_CommandLine $parser the command-line parser
	 *                                    context of this logger.
	 */
	public function __construct(Console_CommandLine $parser)
	{
		$this->out = $parser->outputter;
	}




	/**
	 * @param string $level   the log level. One of the
	 *                        {@link Psr\Log\LogLevel} constants.
	 * @param string $message the message to be logged.
	 * @param array  $context optional. Extra information about the message.
	 *                        Can contain placeholder values for the logged
	 *                        message.
	 *
	 * @return void
	 */
	public function log($level, $message, array $context = [])
	{
		switch ($level) {
		case Psr\Log\Level::EMERGENCY:
			$this->emergency($message, $context);
			break;

		case Psr\Log\Level::ALERT:
			$this->alert($message, $context);
			break;

		case Psr\Log\Level::CRITICAL:
			$this->critical($message, $context);
			break;

		case Psr\Log\Level::ERROR:
			$this->error($message, $context);
			break;

		case Psr\Log\Level::WARNING:
			$this->warning($message, $context);
			break;

		case Psr\Log\Level::NOTICE:
			$this->notice($message, $context);
			break;

		case Psr\Log\Level::INFO:
			$this->info($message, $context);
			break;

		case Psr\Log\Level::DEBUG:
		default:
			$this->debug($message, $context);
			break;
		}
	}




	/**
	 * Logs a system-is-unusable message
	 *
	 * @param string $message the message to be logged.
	 * @param array  $context optional. Extra information about the message.
	 *                        Can contain placeholder values for the logged
	 *                        message.
	 *
	 * @return void
	 */
	public function emergency($message, array $context = [])
	{
		if ($this->level >= self::LEVEL_NONE) {
			$this->out->stderr($this->interpolate($message, $context));
		}
	}




	/**
	 * Logs a message when action must be taken immediately
	 *
	 * @param string $message the message to be logged.
	 * @param array  $context optional. Extra information about the message.
	 *                        Can contain placeholder values for the logged
	 *                        message.
	 *
	 * @return void
	 */
	public function alert($message, array $context = [])
	{
		$this->emergency($message, $context);
	}




	/**
	 * Logs a message when a critical conditiona has occurred
	 *
	 * @param string $message the message to be logged.
	 * @param array  $context optional. Extra information about the message.
	 *                        Can contain placeholder values for the logged
	 *                        message.
	 *
	 * @return void
	 */
	public function critical($message, array $context = [])
	{
		$this->emergency($message, $context);
	}




	/**
	 * Logs a message when a runtime error occurs that does not require
	 * immediate action
	 *
	 * @param string $message the message to be logged.
	 * @param array  $context optional. Extra information about the message.
	 *                        Can contain placeholder values for the logged
	 *                        message.
	 *
	 * @return void
	 */
	public function error($message, array $context = [])
	{
		if ($this->level >= self::LEVEL_ERRORS) {
			$this->out->stderr($this->interpolate($message, $context));
		}
	}




	/**
	 * Logs a message when an exceptional occurrance that is not an error
	 * occurs
	 *
	 * For example, use of a deprecated API.
	 *
	 * @param string $message the message to be logged.
	 * @param array  $context optional. Extra information about the message.
	 *                        Can contain placeholder values for the logged
	 *                        message.
	 *
	 * @return void
	 */
	public function warning($message, array $context = [])
	{
		$this->error($message, $context);
	}




	/**
	 * Logs normal, but significant events
	 *
	 * @param string $message the message to be logged.
	 * @param array  $context optional. Extra information about the message.
	 *                        Can contain placeholder values for the logged
	 *                        message.
	 *
	 * @return void
	 */
	public function notice($message, array $context = [])
	{
		if ($this->level >= self::LEVEL_INFO) {
			$this->out->stdout($this->interpolate($message, $context));
		}
	}




	/**
	 * Logs informative, interesting events
	 *
	 * @param string $message the message to be logged.
	 * @param array  $context optional. Extra information about the message.
	 *                        Can contain placeholder values for the logged
	 *                        message.
	 *
	 * @return void
	 */
	public function info($message, array $context = [])
	{
		$this->notice($message, $context);
	}




	/**
	 * Logs detailed debugging information
	 *
	 * @param string $message the message to be logged.
	 * @param array  $context optional. Extra information about the message.
	 *                        Can contain placeholder values for the logged
	 *                        message.
	 *
	 * @return void
	 */
	public function debug($message, array $context = [])
	{
		if ($this->level >= self::LEVEL_DEBUG) {
			$this->out->stdout($this->interpolate($message, $context));
		}
	}




	/**
	 * Sets the current logging level for this logger
	 *
	 * @return SiteCommandLineLogger the current object, for fluent interface.
	 */
	public function setLevel($level)
	{
		$this->level = (integer)$level;
		return $this;
	}




	/**
	 * Interpolates context values into message placeholders
	 *
	 * @param string $message the message.
	 * @param array  $context the placeholder values as a key => value array.
	 *
	 * @return string the interpolated message.
	 */
	protected function interpolate($message, array $context = [])
	{
		$replace = [];

		foreach ($context as $key => $value) {
			$replace['{'.$key.'}'] = $value;
		}

		return strtr($message, $replace);
	}


}

?>
