<?php

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * PSR-3 compliant logger that sends messages to STDOUT and STDERR through
 * a Console_CommandLine_Outputter.
 *
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteCommandLineLogger implements LoggerInterface
{
    /**
     * Show levels emergency, alert, critical.
     */
    public const LEVEL_NONE = 0;

    /**
     * Show levels emergency, alert, critical, error, warning.
     */
    public const LEVEL_ERRORS = 1;

    /**
     * Show information messages.
     */
    public const LEVEL_INFO = 2;

    /**
     * Show detailed debugging information.
     */
    public const LEVEL_DEBUG = 3;

    /**
     * Current logging level.
     *
     * @var int
     *
     * @see SiteCommandLineLogger::setLevel()
     */
    protected $level = self::LEVEL_NONE;

    /**
     * Outputter object that display log messages.
     *
     * @var Console_CommandLine_Outputter
     */
    protected $out;

    /**
     * Creates a new command-line logger.
     *
     * @param Console_CommandLine $parser the command-line parser
     *                                    context of this logger
     */
    public function __construct(Console_CommandLine $parser)
    {
        $this->out = $parser->outputter;
    }

    /**
     * @param string $level   the log level. One of the
     *                        {@link Psr\Log\LogLevel} constants.
     * @param string $message the message to be logged
     * @param array  $context optional. Extra information about the message.
     *                        Can contain placeholder values for the logged
     *                        message.
     */
    public function log($level, $message, array $context = []): void
    {
        match ($level) {
            LogLevel::EMERGENCY => $this->emergency($message, $context),
            LogLevel::ALERT     => $this->alert($message, $context),
            LogLevel::CRITICAL  => $this->critical($message, $context),
            LogLevel::ERROR     => $this->error($message, $context),
            LogLevel::WARNING   => $this->warning($message, $context),
            LogLevel::NOTICE    => $this->notice($message, $context),
            LogLevel::INFO      => $this->info($message, $context),
            default             => $this->debug($message, $context),
        };
    }

    /**
     * Logs a system-is-unusable message.
     *
     * @param string $message the message to be logged
     * @param array  $context optional. Extra information about the message.
     *                        Can contain placeholder values for the logged
     *                        message.
     */
    public function emergency($message, array $context = []): void
    {
        if ($this->level >= self::LEVEL_NONE) {
            $this->out->stderr($this->interpolate($message, $context));
        }
    }

    /**
     * Logs a message when action must be taken immediately.
     *
     * @param string $message the message to be logged
     * @param array  $context optional. Extra information about the message.
     *                        Can contain placeholder values for the logged
     *                        message.
     */
    public function alert($message, array $context = []): void
    {
        $this->emergency($message, $context);
    }

    /**
     * Logs a message when a critical conditiona has occurred.
     *
     * @param string $message the message to be logged
     * @param array  $context optional. Extra information about the message.
     *                        Can contain placeholder values for the logged
     *                        message.
     */
    public function critical($message, array $context = []): void
    {
        $this->emergency($message, $context);
    }

    /**
     * Logs a message when a runtime error occurs that does not require
     * immediate action.
     *
     * @param string $message the message to be logged
     * @param array  $context optional. Extra information about the message.
     *                        Can contain placeholder values for the logged
     *                        message.
     */
    public function error($message, array $context = []): void
    {
        if ($this->level >= self::LEVEL_ERRORS) {
            $this->out->stderr($this->interpolate($message, $context));
        }
    }

    /**
     * Logs a message when an exceptional occurrance that is not an error
     * occurs.
     *
     * For example, use of a deprecated API.
     *
     * @param string $message the message to be logged
     * @param array  $context optional. Extra information about the message.
     *                        Can contain placeholder values for the logged
     *                        message.
     */
    public function warning($message, array $context = []): void
    {
        $this->error($message, $context);
    }

    /**
     * Logs normal, but significant events.
     *
     * @param string $message the message to be logged
     * @param array  $context optional. Extra information about the message.
     *                        Can contain placeholder values for the logged
     *                        message.
     */
    public function notice($message, array $context = []): void
    {
        if ($this->level >= self::LEVEL_INFO) {
            $this->out->stdout($this->interpolate($message, $context));
        }
    }

    /**
     * Logs informative, interesting events.
     *
     * @param string $message the message to be logged
     * @param array  $context optional. Extra information about the message.
     *                        Can contain placeholder values for the logged
     *                        message.
     */
    public function info($message, array $context = []): void
    {
        $this->notice($message, $context);
    }

    /**
     * Logs detailed debugging information.
     *
     * @param string $message the message to be logged
     * @param array  $context optional. Extra information about the message.
     *                        Can contain placeholder values for the logged
     *                        message.
     */
    public function debug($message, array $context = []): void
    {
        if ($this->level >= self::LEVEL_DEBUG) {
            $this->out->stdout($this->interpolate($message, $context));
        }
    }

    /**
     * Sets the current logging level for this logger.
     *
     * @param mixed $level
     *
     * @return SiteCommandLineLogger the current object, for fluent interface
     */
    public function setLevel($level)
    {
        $this->level = (int) $level;

        return $this;
    }

    /**
     * Interpolates context values into message placeholders.
     *
     * @param string $message the message
     * @param array  $context the placeholder values as a key => value array
     *
     * @return string the interpolated message
     */
    protected function interpolate($message, array $context = [])
    {
        $replace = [];

        foreach ($context as $key => $value) {
            $replace['{' . $key . '}'] = $value;
        }

        return strtr($message, $replace);
    }
}
