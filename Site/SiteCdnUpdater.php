<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/dataobjects/SiteAttachmentCdnTaskWrapper.php';
require_once 'Site/dataobjects/SiteAttachmentWrapper.php';
require_once 'Site/dataobjects/SiteImageCdnTaskWrapper.php';
require_once 'Site/dataobjects/SiteImageWrapper.php';
require_once 'Site/dataobjects/SiteMediaCdnTaskWrapper.php';
require_once 'Site/dataobjects/SiteMediaWrapper.php';

/**
 * Application to process queued SiteCdnTasks
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteCdnUpdater extends SiteCommandLineApplication
{
	// {{{ public properties

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

	// }}}
	// {{{ protected properties

	/**
	 * The directory containing the attachment hierarchy
	 *
	 * @var string
	 */
	protected $attachment_file_base;

	/**
	 * The directory containing the image hierarchy
	 *
	 * @var string
	 */
	protected $image_file_base;

	/**
	 * The directory containing the media hierarchy
	 *
	 * @var string
	 */
	protected $media_file_base;

	/**
	 * An array of the tasks to run
	 *
	 * @var array
	 */
	protected $tasks = array();

	// }}}
	// {{{ public function setAttachmentFileBase()

	public function setAttachmentFileBase($attachment_file_base)
	{
		$this->attachment_file_base = $attachment_file_base;
	}

	// }}}
	// {{{ public function setImageFileBase()

	public function setImageFileBase($image_file_base)
	{
		$this->image_file_base = $image_file_base;
	}

	// }}}
	// {{{ public function setMediaFileBase()

	public function setMediaFileBase($media_file_base)
	{
		$this->media_file_base = $media_file_base;
	}

	// }}}
	// {{{ public function run()

	/**
	 * Runs this application
	 */
	public function run()
	{
		$this->initInternal();
		$this->initTasks();

		$this->lock();
		$this->runInternal();
		$this->unlock();
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	/**
	 * Initializes this application
	 */
	protected function initInternal()
	{
		$this->initModules();
		$this->parseCommandLineArguments();
	}

	// }}}
	// {{{ protected function initTasks()

	/**
	 * Initializes all tasks that we want run
	 */
	protected function initTasks()
	{
		$this->initAttachmentTasks();
		$this->initImageTasks();
		$this->initMediaTasks();
	}

	// }}}
	// {{{ protected function initAttachmentTasks()

	protected function initAttachmentTasks()
	{
		$sql = sprintf(
			'select * from AttachmentCdnQueue where error_date %s %s',
			SwatDB::equalityOperator(null), $this->db->quote(null));

		$tasks = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('SiteAttachmentCdnTaskWrapper'));

		// efficiently load attachments
		$attachment_sql = 'select * from Attachment where id in (%s)';
		$attachments = $tasks->loadAllSubDataObjects(
			'attachment',
			$this->db,
			$attachment_sql,
			SwatDBClassMap::get('SiteAttachmentWrapper'));

		foreach ($tasks as $task) {
			if ($task->attachment instanceof SiteAttachment) {
				$task->attachment->setFileBase($this->attachment_file_base);
			}

			$this->tasks[] = $task;
		}
	}

	// }}}
	// {{{ protected function initImageTasks()

	protected function initImageTasks()
	{
		$sql = sprintf('select * from ImageCdnQueue where error_date %s %s',
			SwatDB::equalityOperator(null), $this->db->quote(null));

		$tasks = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('SiteImageCdnTaskWrapper'));

		// efficiently load images
		$image_sql = 'select * from Image where id in (%s)';
		$images = $tasks->loadAllSubDataObjects(
			'image',
			$this->db,
			$image_sql,
			SwatDBClassMap::get('SiteImageWrapper'));

		// efficiently load dimensions
		$dimension_sql = 'select * from ImageDimension where id in (%s)';
		$dimensions = $tasks->loadAllSubDataObjects(
			'dimension',
			$this->db,
			$dimension_sql,
			SwatDBClassMap::get('SiteImageDimensionWrapper'));

		foreach ($tasks as $task) {
			if ($task->image instanceof SiteImage) {
				$task->image->setFileBase($this->image_file_base);
			}

			$this->tasks[] = $task;
		}
	}

	// }}}
	// {{{ protected function initMediaTasks()

	protected function initMediaTasks()
	{
		$sql = sprintf('select * from MediaCdnQueue where error_date %s %s',
			SwatDB::equalityOperator(null), $this->db->quote(null));

		$tasks = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('SiteMediaCdnTaskWrapper'));

		// efficiently load media
		$media_sql = 'select * from Media where id in (%s)';
		$media = $tasks->loadAllSubDataObjects(
			'media',
			$this->db,
			$media_sql,
			SwatDBClassMap::get('SiteMediaWrapper'));

		// efficiently load encodings
		$encoding_sql = 'select * from MediaEncoding where id in (%s)';
		$encodings = $tasks->loadAllSubDataObjects(
			'encoding',
			$this->db,
			$encoding_sql,
			SwatDBClassMap::get('SiteMediaEncodingWrapper'));

		foreach ($tasks as $task) {
			if ($task->media instanceof SiteMedia) {
				$task->media->setFileBase($this->media_file_base);
			}

			$this->tasks[] = $task;
		}
	}

	// }}}

	// run phase
	// {{{ protected function runInternal()

	protected function runInternal()
	{
		$message = Site::_('Running %s queued tasks.');
		$this->debug(sprintf($message."\n", count($this->tasks)), true);

		foreach ($this->tasks as $task) {
			$this->debug($task->getAttemptDescription());

			$task->run($this->cdn);

			$this->debug($task->getResultDescription());
		}

		$this->debug(Site::_('All Done.')."\n", true);
	}

	// }}}

	// boilerplate code
	// {{{ protected function configure()

	protected function configure(SiteConfigModule $config)
	{
		parent::configure($config);

		$this->database->dsn = $config->database->dsn;
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'SiteConfigModule',
			'database' => 'SiteDatabaseModule',
		);
	}

	// }}}
}

?>
