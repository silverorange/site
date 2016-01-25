<?php

require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteCommandLineConfigModule.php';
require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/dataobjects/SiteVideoMediaWrapper.php';
require_once 'Site/exceptions/SiteCommandLineException.php';

/**
 * Application to copy video to the new directory structure
 *
 * Temporary script until we can fix our encoding process to include HLS.
 *
 * @package   Site
 * @copyright 2015-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteVideoMediaMover extends SiteCommandLineApplication
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
	 * @var boolean
	 */
	protected $clean_up = false;

	/**
	 * @var string
	 */
	protected $media_set_shortname;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		parent::__construct($id, $filename, $title, $documentation);

		$this->addCommandLineArgument(
			new SiteCommandLineArgument(
				array('--clean-up'),
				'setCleanUp',
				Site::_(
					'This removes the old file so we are essentially renaming '.
					'instead of copying.'
				)
			)
		);

		$shortname = new SiteCommandLineArgument(
			array('-s', '--shortname'),
			'setMediaSetShortname',
			Site::_('Sets the shortname of the media set we want to move.')
		);

		$shortname->addParameter(
			'string',
			'shortname name must be specified.'
		);

		$this->addCommandLineArgument($shortname);
	}

	// }}}
	// {{{ public function run()

	/**
	 * Runs this application
	 */
	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();

		$this->lock();

		foreach ($this->getMedia() as $media) {
			$this->moveMedia($media);
		}

		$this->unlock();
	}

	// }}}
	// {{{ public function setCleanUp()

	public function setCleanUp($clean_up)
	{
		$this->clean_up = (boolean)$clean_up;
	}

	// }}}
	// {{{ public function setMediaSetShortname()

	public function setMediaSetShortname($shortname)
	{
		$this->media_set_shortname = $shortname;
	}

	// }}}
	// {{{ abstract protected function hasOldPath()

	abstract protected function getOldPath(SiteVideoMedia $media, $shortname);

	// }}}
	// {{{ abstract protected function hasNewPath()

	abstract protected function getNewPath(SiteVideoMedia $media, $shortname);

	// }}}
	// {{{ abstract protected function hasFile()

	abstract protected function hasFile($path);

	// }}}
	// {{{ abstract protected function moveFile()

	abstract protected function moveFile(SiteVideoMedia $media, $old_path,
		$new_path);

	// }}}
	// {{{ abstract protected function cleanUp()

	abstract protected function cleanUp($path);

	// }}}
	// {{{ protected function getMedia()

	protected function getMedia()
	{
		// If we are cleaning up old files then only get the media meida that
		// has already been segmented (has_hls = true). If we are doing the
		// initial move then only get media that hasn't already been segmented.
		return SwatDB::query(
			$this->db,
			sprintf(
				'select * from Media
				where has_hls = %s and media_set in (
					select id from MediaSet where shortname = %s
				)
				order by id',
				$this->db->quote($this->clean_up, 'boolean'),
				$this->db->quote($this->getMediaSet()->shortname, 'text')
			),
			SwatDBClassMap::get('SiteVideoMediaWrapper')
		);
	}

	// }}}
	// {{{ protected function getMediaSet()

	protected function getMediaSet()
	{
		if ($this->media_set_shortname == '') {
			throw new SiteCommandLineException(
				'A media set shortname must be specified.'
			);
		}

		$class_name = SwatDBClassMap::get('SiteMediaSet');

		$media_set = new $class_name();
		$media_set->setDatabase($this->db);

		if (!$media_set->loadByShortname($this->media_set_shortname)) {
			throw new SiteCommandLineException(
				sprintf(
					'Unable to load media set with shortname “%s”.',
					$this->media_set_shortname
				)
			);
		}

		return $media_set;
	}

	// }}}
	// {{{ protected function moveMedia()

	protected function moveMedia(SiteVideoMedia $media)
	{
		foreach ($media->media_set->encodings as $encoding) {
			if ($media->encodingExists($encoding->shortname)) {
				$this->debug(
					sprintf(
						"Copying %s for %s:",
						$encoding->shortname,
						$media->id
					)
				);

				$old_path = $this->getOldPath($media, $encoding->shortname);
				$new_path = $this->getNewPath($media, $encoding->shortname);

				$old_exists = $this->hasFile($old_path);
				$new_exists = $this->hasFile($new_path);

				if ($new_exists) {
					$this->debug(
						sprintf(
							" file %s has already been moved to %s.\n",
							$old_path,
							$new_path
						)
					);

					if ($this->clean_up) {
						$this->debug("Cleaning up {$old_path}:");
						$this->cleanUp($old_path);
						$this->debug(" complete.\n");
					}
				} elseif (!$old_exists) {
					$this->debug(
						sprintf(
							" unable to locate %s.\n",
							$old_path
						)
					);
				} else {
					$this->moveFile($media, $old_path, $new_path);

					$this->debug(" complete. {$old_path} -> {$new_path}\n");

					if ($this->clean_up) {
						$this->debug("Cleaning up {$old_path}:");
						$this->cleanUp($old_path);
						$this->debug(" complete.\n");
					}
				}
			}
		}
	}

	// }}}
	// {{{ protected function getOldFilename()

	protected function getOldFilename(SiteVideoMedia $media, $shortname)
	{
		$binding = $media->getEncodingBinding($shortname);

		if ($media->media_set->obfuscate_filename) {
			$filename = $media->filename;
		} else {
			$filename = $media->id;
		}

		return sprintf('%s.%s', $filename, $binding->media_type->extension);
	}

	// }}}
	// {{{ protected function getNewFilename()

	protected function getNewFilename(SiteVideoMedia $media, $shortname)
	{
		$binding = $media->getEncodingBinding($shortname);

		if ($media->media_set->obfuscate_filename) {
			$filename = $media->filename;
		} else {
			$filename = $shortname;
		}

		return sprintf('%s.%s', $filename, $binding->media_type->extension);
	}

	// }}}

	// boilerplate code
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'database' => 'SiteDatabaseModule',
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
