<?php

require_once 'Site/SiteVideoImageGenerator.php';
require_once 'Site/dataobjects/SiteVideoMediaWrapper.php';

/**
 * Generates images from video frames
 *
 * @package   Site
 * @copyright 2013-2016 silverorange
 */
class SiteVideoScrubberImageGenerator extends
	SiteVideoImageGenerator
{
	// {{{ public function run()

	public function run()
	{
		parent::run();

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
		$sql = sprintf(
			'select Media.*
			from Media
			inner join MediaSet on Media.media_set = MediaSet.id
			where %s
			order by Media.id',
			$this->getPendingMediaWhereClause()
		);

		return SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('SiteVideoMediaWrapper')
		);
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
		if ($m instanceof SiteVideoMedia) {
			foreach ($m->media_set->encodings as $encoding) {
				if ($encoding->width !== null &&
					$encoding->width > $m->getScrubberImageWidth()) {
					$encoding_shortname = $encoding->shortname;
				}
			}
		}

		if ($encoding_shortname == '') {
			throw new SiteCommandLineException('No encodings big enough');
		}

		return $encoding_shortname;
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
			$thumb = $this->getImagickFrame($movie, $position);
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
			$this->debug(
				sprintf(
					'%d of %d (%d%%)',
					$count,
					$media->getDefaultScrubberImageCount(),
					$count / $media->getDefaultScrubberImageCount() * 100
				)
			);
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

		unlink($tmp_file);

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
	// {{{ protected function getImagickFrame()

	protected function getImagickFrame(FFmpegMovie $movie, $time)
	{
		$frame = $movie->getFrameAtTime($time);
		$img = $frame->toGDImage();
		ob_start();
		imagejpeg($img);
		$imagick = new Imagick();
		$imagick->readImageBlob(ob_get_clean());
		return $imagick;
	}

	// }}}
}

?>
