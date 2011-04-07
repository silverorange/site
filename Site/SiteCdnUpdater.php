<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/dataobjects/SiteImageWrapper.php';
require_once 'Site/dataobjects/SiteImageCdnTaskWrapper.php';

/**
 * Application to process queued SiteCdnTasks
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteCdnUpdater extends SiteCommandLineApplication
{
	// {{{ public properties

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

	/**
	 * The directory containing the image hierarchy
	 *
	 * @var string
	 */
	public $image_dir;

	// }}}
	// {{{ protected properties

	/**
	 * An array of the tasks to run
	 *
	 * @var array
	 */
	protected $tasks = array();

	// }}}
	// {{{ public function run()

	/**
	 * Runs this application
	 */
	public function run()
	{
		$this->initInternal();

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
		$this->initTasks();
	}

	// }}}
	// {{{ protected function initTasks()

	/**
	 * Initializes all tasks that we want run
	 */
	protected function initTasks()
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
				$task->image->setFileBase($this->image_dir);
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
}

?>
