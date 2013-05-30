<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Console/CommandLine.php';
require_once 'Psr/Log/LoggerInterface.php';

/**
 * Abstract class for performing a Gearman job
 *
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteGearmanJobExecutor
{
	// {{{ protected properties

	/**
	 * The command-line context of this job executor.
	 *
	 * @var Console_CommandLine
	 */
	protected $parser = null;

	/**
	 * The logging interface of this job executor.
	 *
	 * @var Psr\Log\LoggingInterface
	 */
	protected $logger = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new job executor
	 *
	 * @param Console_CommandLine     the command-line context.
	 * @param Psr\Log\LoggerInterface the logging interface to use.
	 */
	public function __construct(
		Console_CommandLine $parser,
		Psr\Log\LoggerInterface $logger
	) {
		$this->parser = $parser;
		$this->logger = $logger;
		$this->init();
	}

	// }}}
	// {{{ abstract public function run()

	/**
	 * Completes a job
	 *
	 * Subclasses must implement this method to perform work.
	 *
	 * @param GearmanJob $job
	 */
	abstract public function run(GearmanJob $job);

	// }}}
	// {{{ protected function init()

	/**
	 * Performs any initilization of this job executor
	 *
	 * Subclasses should extend this method to add any required start-up
	 * initialization.
	 *
	 * @return void
	 */
	protected function init()
	{
	}

	// }}}
}

?>
