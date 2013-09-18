<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Psr/Log/LoggerInterface.php';
require_once 'Console/CommandLine.php';
require_once 'Site/Site.php';
require_once 'Site/SiteApplication.php';
require_once 'Site/SiteGearmanCommandLine.php';

/**
 * Application that does a gearman task
 *
 * Example:
 * <code>
 * <?php
 *
 * $parser   = SiteGearmanCommandLine::fromXMLFile('my-cli.xml');
 * $logger   = new SiteCommandLineLogger($parser);
 * $worker   = new GearmanWorker();
 * $app      = new MyGearmanApplication(
 *     'my-task',
 *     $parser,
 *     $logger,
 *     $worker
 * );
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
abstract class SiteGearmanApplication extends SiteApplication
{
	// {{{ protected properties

	/**
	 * The Gearman function name of this application
	 *
	 * @var string
	 */
	protected $function = '';

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
	 * The Gearman worker of this application
	 *
	 * @param GearmanWorker
	 */
	protected $worker = null;

	/**
	 * Error handler that existed before the work loop started
	 *
	 * @var Callable
	 */
	protected $previous_error_handler = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new Gearman application
	 *
	 * @param string                  $function the Gearman function name.
	 * @param Console_CommandLine     $parser   the commane-line context.
	 * @param Psr\Log\LoggerInterface $logger   the logging interface.
	 * @param GearmanWorker           $worker   the Gearman worker.
	 * @param string                  $config   optional. The filename of the
‐    *                                          configuration file. If not
‐    *                                          specified, no special
	 *                                          configuration is performed.
	 */
	public function __construct(
		$function,
		Console_CommandLine $parser,
		Psr\Log\LoggerInterface $logger,
		GearmanWorker $worker,
		$config = null
	) {
		parent::__construct('gearman-' . $function, $config);

		$this->function = $function;
		$this->logger   = $logger;
		$this->parser   = $parser;
		$this->worker   = $worker;
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
			$this->connect();
			$this->register();
			$this->work();
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
	// {{{ abstract public function doWork()

	/**
	 * Completes a job
	 *
	 * Subclasses must implement this method to perform work.
	 *
	 * @param GearmanJob $job
	 */
	abstract public function doWork(GearmanJob $job);

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
	// {{{ public function handleError()

	/**
	 * Handles warnings and errors that can arise from the gearman module
	 * during the wait loop
	 *
	 * @param integer $errno
	 * @param string  $errstr
	 * @param string  $errfile
	 * @param integer $errline
	 * @param array   $errcontext
	 *
	 * @return boolean
	 *
	 * @see http://php.net/manual/function.set-error-handler.php
	 */
	public function handleError($errno, $errstr, $errfile, $errline, $errcontext)
	{
		// respect PHP's configured error handling
		if ((error_reporting() & $errno) === 0) {
			return;
		}

		$ignored_messages = array(
			'GearmanWorker::wait(): gearman_wait:no active file descriptors',
		);

		// if not an ignorable error, use the previous error handler
		if ($errno !== E_WARNING || !in_array($errstr, $ignored_messages)) {

			if ($this->previous_error_handler === null) {
				// use internal error handler
				return false;
			}

			return call_user_func(
				$this->previous_error_handler,
				$errno,
				$errstr,
				$errfile,
				$errline,
				$errcontext
			);

		}
	}

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
	// {{{ protected function connect()

	/**
	 * Connects the Gearman worker to the gearmand server
	 *
	 * @return void
	 */
	protected function connect()
	{
		$this->logger->debug(
			Site::_('Connecting worker to job server {address}:{port} ... '),
			array(
				'address' => $this->cli->args['address'],
				'port'    => $this->cli->options['port']
			)
		);

		$this->worker->addServer(
			$this->cli->args['address'],
			$this->cli->options['port']
		);

		$this->logger->debug(Site::_('done') . PHP_EOL);
	}

	// }}}
	// {{{ protected function register()

	/**
	 * Registers this application's job executor with the gearmand server
	 *
	 * @return void
	 */
	protected function register()
	{
		$this->logger->debug(
			Site::_('Registering "{function}" function ... '),
			array(
				'function' => $this->function
			)
		);

		$this->worker->addFunction(
			$this->function,
			array($this, 'doWork')
		);

		$this->logger->debug(Site::_('done') . PHP_EOL);
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
		$this->logger->debug(
			'=== ' . Site::_('Ready for work.') . ' ===' .
			PHP_EOL . PHP_EOL
		);

		$this->worker->setTimeout(100);

		// wait() can cause PHP warnings, even when we safely handle the
		// warning case. Use custom handler to ignore selected warnings.
		$this->previous_error_handler = set_error_handler(
			array($this, 'handleError')
		);

		while (1) {
			$this->worker->work();

			if (extension_loaded('pcntl')) {
				pcntl_signal_dispatch();
			}

			if ($this->worker->returnCode() === GEARMAN_SUCCESS) {
				continue;
			}

			if (!$this->worker->wait()) {
				switch ($this->worker->returnCode()) {
				case GEARMAN_TIMEOUT:
					$this->logger->debug(
							'=: ' . Site::_('work loop timeout') . PHP_EOL
					);
					break;
				case GEARMAN_NO_ACTIVE_FDS:
					// not connected to server, wait a bit
					$this->logger->info(
						Site::_('Not connected to any server. Waiting ... ')
					);
					sleep(10);
					$this->logger->info(Site::_('done') . PHP_EOL);
					break;
				default:
					$this->logger->error($worker->error() . PHP_EOL);
					break 2;
				}
			}
		}

		restore_error_handler();
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
	 * @param GearmanJob $job the currently runing job or null if no job is
	 *                        currently running.
	 *
	 * @return void
	 */
	protected function handleSigTerm(GearmanJob $job = null)
	{
		$this->logger->info(Site::_('Got SIGTERM, shutting down.' . PHP_EOL));
		exit();
	}

	// }}}
}

?>
