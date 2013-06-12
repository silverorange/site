<?php

require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteCommandLineConfigModule.php';
require_once 'Site/SiteMultipleInstanceModule.php';
require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/dataobjects/SiteMediaWrapper.php';
require_once 'Site/dataobjects/SiteMediaCdnTask.php';
require_once 'Site/exceptions/SiteCommandLineException.php';

/**
 * Abstract application to queue metadata updates for SiteMedia on a CDN
 *
 * @package   Site
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      Make this a more generic base class for updating CDN based files?
 */
abstract class SiteMediaCdnMetadataUpdater extends SiteCommandLineApplication
{
	// {{{ public properties

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		parent::__construct($id, $filename, $title, $documentation);

		$instance = new SiteCommandLineArgument(array('-i', '--instance'),
			'setInstance', 'Optional. Sets the site instance for which to '.
			'run this application.');

		$instance->addParameter('string',
			'instance name must be specified.');

		$this->addCommandLineArgument($instance);

		$this->initModules();
		$this->parseCommandLineArguments();

		$this->locale = SwatI18NLocale::get();
	}

	// }}}
	// {{{ public function setInstance()

	public function setInstance($shortname)
	{
		putenv(sprintf('instance=%s', $shortname));
		$this->instance->init();
		$this->config->init();
	}

	// }}}
	// {{{ public function run()

	/**
	 * Runs this application
	 */
	public function run()
	{
		$this->lock();

		$this->debug("Queuing metadata updates for Media on CDN.\n", true);

		$this->queueUpdates();

		$this->debug("All done.\n", true);

		$this->unlock();
	}

	// }}}
	// {{{ abstract protected function queueUpdates()

	abstract protected function queueUpdates();

	// }}}
	// {{{ protected function queueCdnTask()

	protected function queueCdnTask(SiteMedia $media,
		SiteMediaEncoding $encoding)
	{
		$class_name = SwatDBClassMap::get('SiteMediaCdnTask');

		$task = new $class_name();
		$task->setDatabase($this->db);

		$task->media     = $media;
		$task->encoding  = $encoding;
		$task->operation = 'copy';
		$task->override_http_headers = serialize(array(
			'Content-Disposition' => sprintf('attachment; filename="%s"',
				$media->getContentDispositionFilename($encoding->shortname)),
			));

		$task->save();
	}

	// }}}

	// boilerplate code
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'database' => 'SiteDatabaseModule',
			'instance' => 'SiteMultipleInstanceModule',
		);
	}

	// }}}
	// {{{ protected function configure()

	protected function configure(SiteConfigModule $config)
	{
		parent::configure($config);

		$this->database->dsn = $config->database->dsn;
	}

	// }}}
}

?>
