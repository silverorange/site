<?php

require_once 'Site/SiteVideoImageGenerator.php';
require_once 'Site/dataobjects/SiteVideoMediaWrapper.php';

/**
 * Generates a poster-frame for videos that don't have one
 *
 * @package   Site
 * @copyright 2013-2016 silverorange
 */
class SiteVideoPosterFrameGenerator extends
	SiteVideoImageGenerator
{
	// {{{ protected properties

	protected $poster_frame_position = 1;
	protected $jpeg_quality = 100;

	// }}}
	// {{{ public function run()

	public function run()
	{
		parent::run();

		$this->lock();

		$pending_media = $this->getPendingMedia();
		$this->debug(count($pending_media)." pending videos\n\n");

		foreach ($pending_media as $media) {
			$this->debug("Generating poster frame for media: ".$media->id."\n");
			$media->setFileBase($this->media_file_base);
			$path = $this->getLargestPath($media);
			if ($path == '') {
				$this->debug(
					"Source video not found for media: ".$media->id."\n"
				);
			} else {
				$this->processMedia($media, $path);
			}
		}

		$this->unlock();
		$this->debug("done\n");
	}

	// }}}
	// {{{ protected function getLargestPath()

	protected function getLargestPath(SiteVideoMedia $media)
	{
		$path = null;
		$encoding = $this->getLargestMediaEncoding($media);
		if ($encoding instanceof SiteMediaEncoding) {
			$path = $this->getMediaPath($media, $encoding->shortname);
		}
		return $path;
	}

	// }}}
	// {{{ protected function getLargestMediaEncoding()

	protected function getLargestMediaEncoding(SiteVideoMedia $media)
	{
		$largest_encoding = null;

		$binding = $media->getLargestVideoEncodingBinding();
		foreach ($media->media_set->encodings as $encoding) {
			if ($encoding->id === $binding->media_encoding) {
				$largest_encoding = $encoding;
				break;
			}
		}

		return $largest_encoding;
	}

	// }}}
	// {{{ protected function getPendingMedia()

	protected function getPendingMedia()
	{
		$sql = sprintf(
			'select Media.* from Media
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
		return 'Media.image is null
				and Media.id in (select media from MediaEncodingBinding)
				and MediaSet.id in (select media_set from MediaEncoding
					where width is not null)';
	}

	// }}}
	// {{{ protected function processMedia()

	protected function processMedia(SiteMedia $media, $path)
	{
		$movie = new FFmpegMovie($path);

		$tmp_file = tempnam(sys_get_temp_dir(), 'poster-frame-');
		$poster_frame = $this->saveJpeg(
			$movie,
			$this->poster_frame_position,
			$tmp_file
		);

		$image = $this->getImageObject();
		$image->process($tmp_file);
		$image->save();

		$media->image = $image;
		$media->save();

		unlink($tmp_file);

		$this->debug("\nPoster Frame Saved!\n\n");
	}

	// }}}
	// {{{ protected function getImageObject()

	protected function getImageObject()
	{
		$class_name = SwatDBClassMap::get('SiteVideoImage');

		$image_object = new $class_name();
		$image_object->setDatabase($this->db);
		$image_object->setFileBase($this->image_file_base);

		return $image_object;
	}

	// }}}
	// {{{ protected function saveJpeg()

	protected function saveJpeg(FFmpegMovie $movie, $time, $filename)
	{
		$frame = $movie->getFrameAtTime($time);
		$img = $frame->toGDImage();
		imagejpeg($img, $filename, $this->jpeg_quality);
	}

	// }}}
}

?>
