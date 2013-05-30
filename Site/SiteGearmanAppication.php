<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Psr/Log/LoggerInterface.php';
require_once 'Console/CommandLine.php';
require_once 'Site/Site.php';
require_once 'Site/SiteApplication.php';
require_once 'Site/SiteGearmanJobExecutor.php';

/**
 * Application that does a gearman task
 *
 * Example:
 * <code>
 * <?php
 *
 * $parser   = Console_CommandLine::fromXmlFile('my-cli.xml');
 * $logger   = new SiteCommandLineLogger($parser);
 * $executor = new MyTask($parser, $logger);
 * $worker   = new GearmanWorker();
 * $app      = new SiteGearmanApplication(
 *     'my-task',
 *     $parser,
 *     $logger,
 *     $executor,
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
class SiteGearmanApplication extends SiteApplication
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
	 * The job executor of this application
	 *
	 * @var SiteGearmanJobExecutor
	 */
	protected $executor = null;

	/**
	 * The Gearman worker of this application
	 *
	 * @param GearmanWorker
	 */
	protected $worker = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new Gearman application
	 *
	 * @param string                  $function the Gearman function name.
	 * @param Console_CommandLine     $parser   the commane-line context.
	 * @param Psr\Log\LoggerInterface $logger   the logging interface.
	 * @param SiteGearmanJobExecutor  $executor the job executor.
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
		SiteGearmanJobExecutor $executor,
		GearmanWorker $worker,
		$config = null
	) {
		parent::__construct('gearman-' . $function, $config);

		$this->function = $function;
		$this->logger   = $logger;
		$this->parser   = $parser;
		$this->executor = $executor;
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
		try {
			$this->cli = $this->parser->parse();
			$this->logger->setLevel($this->cli->options['verbose']);
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

		$this->worker->setTimeout($this->cli->options['timeout']);
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
			array($this->executor, 'run')
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

		while ($this->worker->work()) {
			if ($this->worker->returnCode() !== GEARMAN_SUCCESS) {
				$this->logger->error($worker->error() . PHP_EOL);
			}
		}
	}

	// }}}
}

?>
