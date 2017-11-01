<?php

/**
 * Generates media thumbnails for the video scrubber
 *
 * @package   Site
 * @copyright 2013-2016 silverorange
 */
class SiteVideoScrubberImageGenerator extends
	SiteCommandLineApplication
{
	// {{{ protected properties

	/**
	 * The directory containing the media hierarchy
	 *
	 * @var string
	 */
	protected $media_file_base;

	/**
	 * The directory containing the image hierarchy
	 *
	 * @var string
	 */
	protected $image_file_base;

	// }}}

	// {{{ public function setMediaFileBase()

	public function setMediaFileBase($media_file_base)
	{
		$this->media_file_base = $media_file_base;
	}

	// }}}
	// {{{ public function setImageFileBase()

	public function setImageFileBase($image_file_base)
	{
		$this->image_file_base = $image_file_base;
	}

	// }}}
	// {{{ public function run()

	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();

		if ($this->image_file_base === null) {
			throw new SiteCommandLineException('Image file base must be set');
		} elseif ($this->media_file_base === null) {
			throw new SiteCommandLineException('Media file base must be set');
		}

		$this->lock();

		$pending_media = $this->getPendingMedia();
		$this->debug(count($pending_media)." pending videos\n\n");

		if (count($pending_media) > 0) {
			$encoding_shortname = $this->getMediaEncodingShortname(
				$pending_media);

			foreach ($pending_media as $media) {
				$this->debug("Media: ".$media->id."\n");
				$media->setFileBase($this->media_file_base);
				$path = $this->getMediaPath($media, $encoding_shortname);
				if ($path === null) {
					continue;
				}

				$this->processMedia($media, $path);
			}
		}

		$this->unlock();
		$this->debug("done\n");
	}

	// }}}
	// {{{ protected function getPendingMedia()

	protected function getPendingMedia()
	{
		$sql = sprintf('select Media.*
			from Media
			inner join MediaSet on Media.media_set = MediaSet.id
			where %s
			order by Media.id',
			$this->getPendingMediaWhereClause());

		$media = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('SiteVideoMediaWrapper'));

		return $media;
	}

	// }}}
	// {{{ protected function getPendingMediaWhereClause()

	protected function getPendingMediaWhereClause()
	{
		return 'Media.scrubber_image is null
				and Media.id in (select media from MediaEncodingBinding)
				and MediaSet.id in (select media_set from MediaEncoding
					where width is not null)';
	}

	// }}}
	// {{{ protected function getMediaEncodingShortname()

	protected function getMediaEncodingShortname(SiteMediaWrapper $media)
	{
		$encoding_shortname = null;

		// TODO: switch to caching the encoding-shortname per media-set
		$m = $media->getFirst();
		if ($m !== null) {
			foreach ($m->media_set->encodings as $encoding) {
				if ($encoding->width !== null &&
					$encoding->width > $m->getScrubberImageWidth()) {
					$encoding_shortname = $encoding->shortname;
				}
			}
		}

		if ($encoding_shortname === null) {
			throw new SiteCommandLineException('No encodings big enough');
		}

		return $encoding_shortname;
	}

	// }}}
	// {{{ protected function getMediaPath()

	protected function getMediaPath(SiteMedia $media, $encoding_shortname)
	{
		$path = null;

		if ($media->encodingExists($encoding_shortname)) {
			$path = $media->getFilePath($encoding_shortname);

			if (!file_exists($path)) {
				$message = "'".$path."' not found for media ".$media->id;
				$exception = new SiteCommandLineException($message);
				$exception->processAndContinue();
				$this->debug($message."\n\n");
				$path = null;
			}
		} else {
			$message = "Encoding '".$encoding_shortname."' not found for ".
				"media ".$media->id;

			$exception = new SiteCommandLineException($message);
			$exception->processAndContinue();
			$this->debug($message."\n\n");
		}

		return $path;
	}

	// }}}
	// {{{ protected function processMedia()

	protected function processMedia(SiteMedia $media, $path)
	{
		$movie = new FFmpegMovie($path);
		$grid = new Imagick();

		$position = 0;
		$count = 0;

		$this->debug("Processing Frames:\n");

		while ($position < $movie->getDuration() - 2) {
			$frame = $movie->getFrameAtTime($position);
			$img = $frame->toGDImage();
			ob_start();
			imagejpeg($img);
			$thumb = new Imagick();
			$thumb->readImageBlob(ob_get_clean());
			$thumb->resizeImage(
				$media->getScrubberImageWidth(),
				$media->getScrubberImageWidth(),
				Imagick::FILTER_LANCZOS,
				1,
				true);

			$grid->addImage($thumb);

			$position += $media->getScrubberImageInterval();
			$count++;

			$this->debug("\033[100D"); // reset the line
			$this->debug(sprintf('%d of %d (%d%%)',
				$count,
				$media->getDefaultScrubberImageCount(),
				$count / $media->getDefaultScrubberImageCount() * 100));
		}

		$grid->resetIterator();
		$output = $grid->appendImages(false);
		$output->setImageFormat('jpeg');
		$tmp_file = tempnam(sys_get_temp_dir(), 'scrubber-image-');
		$output->writeImage($tmp_file);

		if ($media->scrubber_image instanceof SiteVideoScrubberImage) {
			$media->scrubber_image->setFileBase($this->image_file_base);
			$media->scrubber_image->delete();
		}

		$image = $this->getImageObject();
		$image->process($tmp_file);
		$image->save();

		$media->scrubber_image_count = $media->getDefaultScrubberImageCount();
		$media->scrubber_image = $image;
		$media->save();

		$this->debug("\nComposite Saved!\n\n");
	}

	// }}}
	// {{{ protected function getImageObject()

	protected function getImageObject()
	{
		$class_name = SwatDBClassMap::get('SiteVideoScrubberImage');

		$image_object = new $class_name();
		$image_object->setDatabase($this->db);
		$image_object->setFileBase($this->image_file_base);

		return $image_object;
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
