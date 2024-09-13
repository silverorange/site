<?php

/**
 * Recalculates the duration of all media in a media set
 *
 * @package   Site
 * @copyright 2015-2016 silverorange
 */
class SiteAudioMediaDurationUpdater extends SiteCommandLineApplication
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
	protected $dry_run = false;

	/**
	 * @var string
	 */
	protected $media_set_shortname;

	/**
	 * @var string
	 */
	protected $media_file_base;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new private data deleter application
	 *
	 * @param string $id
	 * @param string $config_filename
	 * @param string $title
	 * @param string $documentation
	 *
	 * @see SiteCommandLineApplication::__construct()
	 */
	public function __construct($id, $config_filename, $title, $documentation)
	{
		parent::__construct($id, $config_filename, $title, $documentation);

		$this->addCommandLineArgument(
			new SiteCommandLineArgument(
				['--dry-run'],
				'setDryRun',
				Site::_(
					'Durations are only calculated but not saved. Use '.
					'with --verbose to see what durations differ.'
				)
			)
		);

		$shortname = new SiteCommandLineArgument(
			['-s', '--shortname'],
			'setMediaSetShortname',
			Site::_(
				'Sets the shortname of the media set whose durations we want '.
				'to recalculate.'
			)
		);

		$shortname->addParameter(
			'string',
			'shortname name must be specified.'
		);

		$this->addCommandLineArgument($shortname);
	}

	// }}}
	// {{{ public function run()

	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();

		$this->lock();

		$now = new SwatDate();
		$now->toUTC();

		foreach ($this->getMedia() as $media) {
			$this->updateDuration($media);
		}

		$this->unlock();
		$this->debug(Site::_('done')."\n");
	}

	// }}}
	// {{{ public function setDryRun()

	public function setDryRun($dry_run)
	{
		$this->dry_run = (boolean)$dry_run;
	}

	// }}}
	// {{{ public function setMediaSetShortname()

	public function setMediaSetShortname($shortname)
	{
		$this->media_set_shortname = $shortname;
	}

	// }}}
	// {{{ public function setMediaFileBase()

	public function setMediaFileBase($media_file_base)
	{
		$this->media_file_base = $media_file_base;
	}

	// }}}
	// {{{ protected function getMediaSet()

	protected function getMediaSet()
	{
		if ($this->media_set_shortname == '') {
			throw new SiteException('A media set shortname must be specified.');
		}

		$class_name = SwatDBClassMap::get(SiteMediaSet::class);

		$media_set = new $class_name();
		$media_set->setDatabase($this->db);

		if (!$media_set->loadByShortname($this->media_set_shortname)) {
			throw new SiteException(
				sprintf(
					'Unable to load media set with shortname “%s”.',
					$this->media_set_shortname
				)
			);
		}

		return $media_set;
	}

	// }}}
	// {{{ protected function getMedia()

	protected function getMedia()
	{
		$sql = sprintf(
			'select * from Media where media_set = %s order by id',
			$this->db->quote($this->getMediaSet()->id, 'integer')
		);

		$audio = SwatDB::query(
			$this->db,
			$sql,
			$this->getAudioMediaWrapperClass()
		);

		return $audio;
	}

	// }}}
	// {{{ protected function getAudioMediaWrapperClass()

	protected function getAudioMediaWrapperClass()
	{
		return SwatDBClassMap::get(SiteAudioMediaWrapper::class);
	}

	// }}}
	// {{{ protected function updateDuration()

	protected function updateDuration(SiteAudioMedia $media)
	{
		if ($this->media_file_base == '') {
			throw new SiteException('A media file base must be specified.');
		}

		$media->setFileBase($this->media_file_base);

		$filename = $media->getFilePath('original');

		if (file_exists($filename)) {
			$old_duration = $media->duration;
			$new_duration = $media->parseDuration($this, $filename);

			$this->debug(
				sprintf(
					Site::_('Updating Media %s...'),
					$media->id
				)
			);

			if ($old_duration !== $new_duration) {
				$this->debug("\n");
				$this->debug(
					sprintf(
						"\t".Site::_('new duration: %s')."\n",
						$new_duration
					)
				);

				$this->debug(
					sprintf(
						"\t".Site::_('old duration: %s')."\n",
						$old_duration
					)
				);

				if (!$this->dry_run) {
					$this->saveMedia($media, $new_duration);
				}
			} else {
				$this->debug(Site::_(' existing duration correct.')."\n");
			}
		} else {
			$this->debug(
				sprintf(
					Site::_('Unable to locate “%s” for duration checking.'),
					$filename
				)."\n"
			);
		}
	}

	// }}}
	// {{{ protected function saveMedia()

	protected function saveMedia(SiteMedia $media, $duration)
	{
		$media->duration = $duration;
		$media->save();
	}

	// }}}

	// boilerplate
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this search indexer
	 *
	 * @return array the list of modules to load for this application.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		return array_merge(
			parent::getDefaultModuleList(),
			[
				'config' => SiteConfigModule::class,
				'database' => SiteDatabaseModule::class,
				'amqp' => SiteAMQPModule::class,
			]
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
