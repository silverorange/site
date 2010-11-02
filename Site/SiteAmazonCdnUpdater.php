<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteAmazonCdnModule.php';
require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/dataobjects/SiteImageCdnTaskWrapper.php';

class SiteAmazonCdnUpdater extends SiteCommandLineApplication
{
	// {{{ public properties

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

	/**
	 * The base directory the files are saved to
	 *
	 * @var string
	 */
	public $source_dir;

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
	// {{{ protected function runInternal()

	/**
	 * Runs this application
	 */
	protected function runInternal()
	{
		$tasks = $this->getTasks();

		foreach ($tasks as $task) {
			try {
				switch ($task->operation) {
				case 'copy':
					$this->copyImage($task);
					break;
				case 'delete':
					$this->deleteImage($task);
					break;
				}

				$task->delete();
			} catch (SwatFileNotFoundException $e) {
				$exception = $e;
				$exception->process(false);
			} catch (Services_Amazon_S3_Exception $e) {
				$exception = new SwatException($e);
				$exception->process(false);
			}
		}
	}

	// }}}
	// {{{ protected function copyImage()

	/**
	 * Copies this taks's image to a CDN
	 *
	 * @param SiteImageCdnTask $task the copy task.
	 */
	protected function copyImage(SiteImageCdnTask $task)
	{
		$image     = $this->getImage($this->getImageId($task));
		$shortname = $this->getDimensionShortname($task);

		if ($image instanceof SiteImage) {
			$this->cdn->copyFile(
				$image->getFilePath($shortname),
				$task->image_path,
				$image->getMimeType($shortname));

			$image->setOnCdn(true, $shortname);
		}
	}

	// }}}
	// {{{ protected function deleteImage()

	/**
	 * Deletes this taks's image to a CDN
	 *
	 * @param SiteImageCdnTask $task the delete task.
	 */
	protected function deleteImage(SiteImageCdnTask $task)
	{
		$image     = $this->getImage($this->getImageId($task));
		$shortname = $this->getDimensionShortname($task);

		if ($image instanceof SiteImage) {
			$image->setOnCdn(false, $shortname);
		}

		$this->cdn->deleteFile($task->image_path);
	}

	// }}}
	// {{{ protected function getTasks()

	/**
	 * Gets all outstanding CDN tasks
	 *
	 * @return SiteImageCdnTaskWrapper a recordset wrapper of all current tasks.
	 */
	protected function getTasks()
	{
		$wrapper = SwatDBClassMap::get('SiteImageCdnTaskWrapper');

		$sql = 'select * from ImageCdnQueue';

		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
	// {{{ protected function getImage()

	/**
	 * Gets this task's image
	 *
	 * @return SiteImage this task's image.
	 */
	protected function getImage($id)
	{
		$class_name = SwatDBClassMap::get('SiteImage');

		$image = new $class_name();
		$image->setDatabase($this->db);
		$image->setFileBase($this->source_dir);

		if ($image->load($id)) {
			return $image;
		} else {
			return null;
		}
	}

	// }}}
	// {{{ protected function getImageId()

	/**
	 * Gets the id of this task's image
	 *
	 * @param SiteImageCdnTask $task the task who's image id you want.
	 *
	 * @return integer the id of the task's image.
	 */
	protected function getImageId(SiteImageCdnTask $task)
	{
		$ruins  = explode('/', $task->image_path);
		$debris = explode('.', $ruins[3]);

		return intval($debris[0]);
	}

	// }}}
	// {{{ protected function getDimensionShortname()

	/**
	 * Gets the shortname of this task's image dimension
	 *
	 * @param SiteImageCdnTask $task the task who's shortname you want.
	 *
	 * @return string the shortname of this task's image dimension.
	 */
	protected function getDimensionShortname(SiteImageCdnTask $task)
	{
		$debris = explode('/', $task->image_path);

		return $debris[2];
	}

	// }}}

	// boilerplate code
	// {{{ protected function configure()

	protected function configure(SiteConfigModule $config)
	{
		parent::configure($config);

		$this->database->dsn = $config->database->dsn;

		$this->cdn->bucket_id         = $config->amazon->bucket;
		$this->cdn->access_key_id     = $config->amazon->access_key_id;
		$this->cdn->access_key_secret = $config->amazon->access_key_secret;
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'cdn'      => 'SiteAmazonCdnModule',
			'config'   => 'SiteConfigModule',
			'database' => 'SiteDatabaseModule',
		);
	}

	// }}}
}

?>
