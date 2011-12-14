<?php

require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteCommandLineConfigModule.php';
require_once 'Site/SiteMultipleInstanceModule.php';
require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/exceptions/SiteCommandLineException.php';
require_once 'Site/SiteBotrMediaToaster.php';

/**
 * Abstract application for applications that access media on bits on the run.
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      do something better with the isMarkedX() methods. Maybe move the
 *            tags to constants.
 */
abstract class SiteBotrMediaToasterCommandLineApplication
	extends SiteCommandLineApplication
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
	 * A SiteBotrMediaToaster Object for accessing and updating video on BOTR
	 *
	 * @var SiteBotrMediaToaster
	 */
	protected $toaster;

	/**
	 * Array of source files to validate.
	 *
	 * @var array
	 */
	protected $source_files = array();

	/**
	 * Directory where source files are stored.
	 *
	 * @var string
	 */
	protected $source_directory;

	/**
	 * Array of all media returned from Botr
	 *
	 * @var array
	 */
	protected $media;

	/**
	 * Array of media dataobjects that exist in the site's database
	 *
	 * Indexed by BOTR key.
	 *
	 * @var array
	 */
	protected $media_objects = array();

	/**
	 * Various tags added to metadata on BOTR to mark status of video.
	 *
	 * @var string
	 */
	protected $valid_tag_filesize   = 'validated.filesize';
	protected $valid_tag_md5        = 'validated.md5';
	protected $invalid_tag_filesize = 'invalid.filesize';
	protected $invalid_tag_md5      = 'invalid.md5';
	protected $original_missing_tag = 'original_missing';
	protected $duplicate_tag        = 'duplicate';
	protected $encoded_tag          = 'encoded';
	protected $imported_tag         = 'imported';
	protected $delete_tag           = 'delete';
	protected $ignored_tag          = 'ignored';
	protected $original_deleted_tag = 'original.deleted';

	protected $locale;

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

		$this->initInternal();
		$this->runInternal();

		$this->debug("All done.\n", true);

		$this->unlock();
	}

	// }}}
	// {{{ public function setSourceDirectory()

	public function setSourceDirectory($directory)
	{
		$this->source_directory = $directory;
	}

	// }}}
	// {{{ abstract protected function runInternal()

	abstract protected function runInternal();

	// }}}

	// init
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->initToaster();
	}

	// }}}
	// {{{ protected function initToaster()

	protected function initToaster()
	{
		$this->toaster = new SiteBotrMediaToaster($this);
	}

	// }}}
	// {{{ protected function getSourceFiles()

	protected function getSourceFiles()
	{
		if (count($this->source_files) == 0) {
			$iterator = new RecursiveDirectoryIterator(
				$this->getSourceDirectory());

			// only grab media types we upload.
			$valid_extensions = array(
				'mp4',
				'mov',
				'm4v',
				'm4a',
				'mp3',
				);

			// only add videos if they belong to certain directories
			$valid_directories = array(
				'video',
				'videos',
				'samples',
				'video-extras',
				);

			foreach (new RecursiveIteratorIterator($iterator) as
				$path => $file) {
				// Catch exception and just treat as a file that needs further
				// investigation.
				$key = $file->getFileName();

				// ignore .DS_Store and ._ files
				$skip = ((substr($key, 0, 2) == '._') || $key == '.DS_Store');

				$extension = $this->getExtension($key);
				$directory = end(explode('/', $file->getPath()));

				if ($skip === false &&
					array_search($extension, $valid_extensions) !== false &&
					array_search($directory, $valid_directories) !== false) {
					$old_error_handler =
						set_error_handler('SourceFileErrorHandler');

					try {
						$this->addSourceFile($key, $path, $file);
					} catch(Exception $e) {
						$this->handleSourceFileException($key, $path, $file,
							$e);
					}

					restore_error_handler();
				}
			}
		}

		return $this->source_files;
	}

	// }}}
	// {{{ protected function addSourceFile()

	protected function addSourceFile($key, $path, SplFileInfo $file)
	{
		if (isset($this->source_files[$key])) {
			throw new SiteCommandLineException(sprintf(
				"Source file ‘%s’ duplicate.\nVersion 1: %s\n Version 2: %s",
				$path,
				$this->source_files[$key]['path']));
		}

		$this->source_files[$key]['path'] = $path;
		$this->source_files[$key]['md5']  = $this->getMd5($path);
	}

	// }}}
	// {{{ protected function getExtension()

	protected function getExtension($filename)
	{
		$extension = null;

		$info = pathinfo($filename);
		if (isset($info['extension'])) {
			$extension = $info['extension'];
		}

		return $extension;
	}

	// }}}
	// {{{ protected function getMd5()

	protected function getMd5($path)
	{
		// md5 is crazy slow on the large video files, so load from an external
		// md5 file that only needs to be generated once.
		$md5_filename = str_replace($this->getExtension($path), 'md5', $path);

		if (file_exists($md5_filename)) {
			$md5 = file_get_contents($md5_filename);
		} else {
			$md5 = md5_file($path);
			file_put_contents($md5_filename, $md5);
		}

		return $md5;
	}

	// }}}
	// {{{ protected function handleSourceFileException()

	protected function handleSourceFileException($key, $path, SplFileInfo $file,
		Exception $e)
	{
		// do nothing by default.
	}

	// }}}
	// {{{ protected function getSourceDirectory()

	protected function getSourceDirectory()
	{
		$directory = $this->source_directory;

		if ($this->getInstance() !== null) {
			$directory.= '/'.$this->getInstance()->shortname;
		}

		return $directory;
	}

	// }}}
	// {{{ protected function getMedia()

	protected function getMedia(array $options = array())
	{
		if ($this->media === null) {
			$media = $this->toaster->listMedia($options);

			foreach ($media as $media_file) {
				$this->media[$media_file['key']] = $media_file;
			}
		}

		return $this->media;
	}

	// }}}
	// {{{ protected function getMediaObjects()

	protected function getMediaObjects()
	{
		if ($this->media_objects == null) {
			$sql = $this->getMediaObjectSql();

			$objects = SwatDB::query($this->db, $sql,
				SwatDBClassMap::get('SiteBotrMediaWrapper'));

			$this->media_objects = array();
			foreach ($objects as $object) {
				$this->media_objects[$object->key] = $object;
			}
		}

		return $this->media_objects;
	}

	// }}}
	// {{{ protected function getMediaObjectSql()

	protected function getMediaObjectSql()
	{
		$where = $this->getMediaObjectWhere();
		$join  = $this->getMediaObjectJoin();

		$sql = sprintf(
			'select Media.* from Media %s where 1=1 %s',
			$join,
			$where);

		return $sql;
	}

	// }}}
	// {{{ protected function getMediaObjectWhere()

	protected function getMediaObjectWhere()
	{
		return null;
	}

	// }}}
	// {{{ protected function getMediaObjectJoin()

	protected function getMediaObjectJoin()
	{
		return null;
	}

	// }}}
	// {{{ protected function resetMediaCache()

	protected function resetMediaCache()
	{
		$this->media = null;
	}

	// }}}
	// {{{ protected function mediaFileIsMarkedValid()

	protected function mediaFileIsMarkedValid(array $media_file)
	{
		$valid = false;

		if ((strpos($media_file['tags'], $this->valid_tag_filesize)
			!== false) ||
			(strpos($media_file['tags'], $this->valid_tag_md5) !== false)) {
			$valid = true;
		}

		return $valid;
	}

	// }}}
	// {{{ protected function mediaFileIsMarkedInvalid()

	protected function mediaFileIsMarkedInvalid(array $media_file)
	{
		$invalid = false;

		if ((strpos($media_file['tags'], $this->invalid_tag_filesize)
			!== false) ||
			(strpos($media_file['tags'], $this->invalid_tag_md5) !== false)) {
			$invalid = true;
		}

		return $invalid;
	}

	// }}}
	// {{{ protected function mediaFileIsMarkedEncoded()

	protected function mediaFileIsMarkedEncoded(array $media_file)
	{
		$encoded = false;

		if ((strpos($media_file['tags'], $this->encoded_tag) !== false)) {
			$encoded = true;
		}

		return $encoded;
	}

	// }}}
	// {{{ protected function mediaFileIsIgnorable()

	protected function mediaFileIsIgnorable(array $media_file)
	{
		$ignorable = false;

		if ((strpos($media_file['tags'], $this->delete_tag) !== false) ||
			(strpos($media_file['tags'], $this->ignored_tag) !== false)) {
			$invalid = true;
		}

		return $ignorable;
	}

	// }}}
	// {{{ protected function mediaFileOriginalIsDownloadable()

	protected function mediaFileOriginalIsDownloadable(array $media_file)
	{
		$downloadable = false;

		// don't download originals for videos that are marked to be downloaded,
		// and only download the originals for those marked with the original
		// missing. Ignore videos that aren't imported or are marked to be
		// deleted.
		if ((strpos($media_file['tags'], $this->delete_tag) === false) &&
			((strpos($media_file['tags'], $this->imported_tag) != false)) &&
			(strpos($media_file['tags'], $this->original_missing_tag)
				!= false)) {
			$downloadable = true;
		}

		return $downloadable;
	}

	// }}}
	// {{{ protected function download($from, $to)

	protected function download($source, $destination, $prefix = null,
		$filesize_to_check = null)
	{
		if (file_exists($destination)) {
			throw new SiteCommandLineException(sprintf(
				'File already exists “%s”', $destination));
		}

		// separate the prefix with a dash for prettier temp filenames
		if ($prefix !== null) {
			$prefix.='-';
		}

		// add SiteBotrMedia to the prefix for easier searching on the temp
		// filename.
		$prefix    = 'SiteBotrMedia-'.$prefix;
		$info      = pathinfo($destination);
		$directory = $info['dirname'];
		$temp_file = tempnam(sys_get_temp_dir(), $prefix);

		if (!file_exists($directory) && !mkdir($directory, 0777, true)) {
			throw new SiteCommandLineException(sprintf(
				'Unable to create directory “%s.”', $directory));
		}

		if (!copy($source, $temp_file)) {
			throw new SiteCommandLineException(sprintf(
				'Unable to download “%s” to “%s.”', $source, $temp_path));
		}

		/* TODO - > 2gb filesize support */
		if ($filesize_to_check < 2147483648) {
			$local_filesize = filesize($temp_file);
			if ($local_filesize != $filesize_to_check) {
				unlink($temp_file);
				throw new SiteCommandLineException(sprintf(
					"Downloaded file size mismatch\n".
					"%s bytes on BOTR\n".
					"%s bytes locally.",
					$filesize_to_check,
					$local_filesize));
			}
		}

		if (!rename($temp_file, $destination)) {
			throw new SiteCommandLineException(sprintf(
				'Unable to move “%s” to “%s.”', $temp_file, $destination));
		}
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

// {{{ function SourceFileErrorHandler()

function SourceFileErrorHandler($errno, $errstr, $errfile, $errline)
{
	throw new SiteCommandLineException($errstr, $errno, 0, $errfile, $errline);
}

// }}}

?>
