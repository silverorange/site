<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Psr/Log/LoggerInterface.php';
require_once 'Console/CommandLine.php';
require_once 'Site/Site.php';
require_once 'Site/SiteApplication.php';
require_once 'Site/SiteAMQPCommandLine.php';
require_once 'Site/SiteAMQPJob.php';

/**
 * Application that does a AMQP task
 *
 * Example:
 * <code>
 * <?php
 *
 * $parser = SiteAMQPCommandLine::fromXMLFile('my-cli.xml');
 * $logger = new SiteCommandLineLogger($parser);
 * $app    = new MyAMQPApplication('my-task', $parser, $logger);
 *
 * $app();
 *
 * ?>
 * </code>
 *
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteAMQPApplication extends SiteApplication
{
	// {{{ class constants

	/**
	 * How long to wait when the queue is empty before checking again
	 *
	 * In milliseconds.
	 */
	const WORK_LOOP_TIMEOUT = 100;

	// }}}
	// {{{ protected properties

	/**
	 * The AMQP queue name of this application
	 *
	 * @var string
	 */
	protected $queue = '';

	/**
	 * The command-line context of this application.
	 *
	 * @var Console_CommandLine
	 */
	protected $parser = null;

	/**
	 * The logging interface of this application.
	 *
	 * @var Psr\Log\LoggingInterface
	 */
	protected $logger = null;

	/**
	 * @var AMQPExchange
	 */
	protected $exchange = null;

	/**
	 * @var AMQPChannel
	 */
	protected $channel = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new Gearman application
	 *
	 * @param string                  $queue  the AQMP queue name.
	 * @param Console_CommandLine     $parser the commane-line context.
	 * @param Psr\Log\LoggerInterface $logger the logging interface.
	 * @param string                  $config optional. The filename of the
‐    *                                        configuration file. If not
‐    *                                        specified, no special
	 *                                        configuration is performed.
	 */
	public function __construct(
		$queue,
		Console_CommandLine $parser,
		Psr\Log\LoggerInterface $logger,
		$config = null
	) {
		parent::__construct('aqmp-' . $queue, $config);

		$this->queue  = $queue;
		$this->logger = $logger;
		$this->parser = $parser;
	}

	// }}}
	// {{{ public function __invoke()

	/**
	 * Runs this application
	 *
	 * @return void
	 */
	public function __invoke()
	{
		if (extension_loaded('pcntl')) {
			pcntl_signal(SIGTERM, array($this, 'handleSignal'));
		}

		$this->initModules();

		try {
			$this->cli = $this->parser->parse();
			$this->logger->setLevel($this->cli->options['verbose']);
			$this->init();

			$connection = new AMQPConnection();
			$connection->setHost($this->cli->args['address']);
			$connection->setPort($this->cli->options['port']);

			// re-connection loop if AMQP server goes away
			while (true) {
				try {
					$this->logger->debug(
						Site::_('Connecting worker to AMQP server {address}:{port} ... '),
						array(
							'address' => $this->cli->args['address'],
							'port'    => $this->cli->options['port']
						)
					);
					$connection->connect();
					$this->channel = new AMQPChannel($connection);
					$this->exchange = new AMQPExchange($this->channel);
					$this->logger->debug(Site::_('done') . PHP_EOL);

					$this->work();
				} catch (AMQPConnectionException $e) {
					$this->logger->debug(Site::_('connection error') . PHP_EOL);
					$this->logger->error($e->getMessage());
					sleep(10);
				}
			}

		} catch (Console_CommandLine_Exception $e) {
			$this->logger->error($e->getMessage() . PHP_EOL);
			exit(1);
		}
	}

	// }}}
	// {{{ public function run()

	/**
	 * Runs this application
	 *
	 * Interface required by SiteApplication.
	 *
	 * @return void
	 */
	public function run()
	{
		$this();
	}

	// }}}
	// {{{ public function handleSignal()

	/**
	 * Handles signals sent to this process
	 *
	 * @var integer $signal the sinal that was received (e.g. SIGTERM).
	 *
	 * @return void
	 */
	public function handleSignal($signal)
	{
		switch ($signal) {
		case SIGTERM:
			$this->handleSigTerm();
			break;
		}
	}

	// }}}
	// {{{ abstract protected function doWork()

	/**
	 * Completes a job
	 *
	 * Subclasses must implement this method to perform work.
	 *
	 * @param SiteAMQPJob $job 
	 */
	abstract protected function doWork(SiteAMQPJob $job);

	// }}}
	// {{{ protected function init()

	/**
	 * Performs any initilization of this application
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
	// {{{ protected function work()

	/**
	 * Enters this application into the work-listen loop
	 *
	 * @return void
	 */
	protected function work()
	{
		$queue = new AMQPQueue($this->channel);
		$queue->setName($this->queue);
		$queue->setFlags(AMQP_DURABLE);
		$queue->declare();

		$this->logger->debug(
			'=== ' . Site::_('Ready for work.') . ' ===' .
			PHP_EOL . PHP_EOL
		);

		while (1) {
			if (extension_loaded('pcntl')) {
				pcntl_signal_dispatch();
			}

			if ($this->canWork()) {
				if (($envelope = $queue->get()) === false) {
					usleep(self::WORK_LOOP_TIMEOUT * 1000);
					$this->logger->debug(
						'=: ' . Site::_('work loop timeout') . PHP_EOL
					);
				} else {
					$this->doWork(
						new SiteAMQPJob(
							$this->exchange,
							$envelope,
							$queue
						)
					);
				}
			}
		}
	}

	// }}}
	// {{{ protected function canWork()

	/**
	 * Provides a place for subclasses to add application-specific timeouts
	 *
	 * For example, if a database server or another service goes away this
	 * can be used to wait for it to return before continuing to do work.
	 *
	 * If work can not be done, the subclass should take responsibility for
	 * adding a sleep() or wait() call in the canWork() method so as not to
	 * overwhelm the processor.
	 *
	 * @return boolean true if work can be done and false if not.
	 */
	protected function canWork()
	{
		return true;
	}

	// }}}
	// {{{ protected function handleSigTerm()

	/**
	 * Provides a safe shutdown function
	 *
	 * When this worker is stopped via a monitoring script sending SIGTERM
	 * this method can safely finish the current job before ending the current
	 * process.
	 *
	 * If the job can't be finished, the job can be cancelled or requeued.
	 * Subclasses must call exit() or parent::handleSigTerm() to ensure
	 * the process ends.
	 *
	 * @return void
	 */
	protected function handleSigTerm()
	{
		$this->logger->info(Site::_('Got SIGTERM, shutting down.' . PHP_EOL));
		exit();
	}

	// }}}
}

?>
