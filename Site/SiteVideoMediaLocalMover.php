<?php

require_once 'Site/SiteVideoMediaMover.php';

/**
 * Application to copy video to the new local directory structure
 *
 * Temporary script until we can fix our encoding process to include HLS.
 *
 * @package   Site
 * @copyright 2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteVideoMediaMover
 */
class SiteVideoMediaLocalMover extends SiteVideoMediaMover
{
	// {{{ protected properties

	protected $file_base;

	// }}}
	// {{{ public function setFileBase()

	public function setFileBase($file_base)
	{
		$this->file_base = $file_base;
	}

	// }}}
	// {{{ public function getFileBase()

	public function getFileBase()
	{
		if ($this->file_base === null) {
			throw new SiteException('File base has not been set.');
		}

		return $this->file_base;
	}

	// }}}
	// {{{ protected function hasOldPath()

	protected function getOldPath(SiteVideoMedia $media, $shortname)
	{
		return sprintf(
			'%s/%s/%s/%s',
			$this->getFileBase(),
			$media->media_set->shortname,
			$shortname,
			$this->getOldFilename($media, $shortname)
		);
	}

	// }}}
	// {{{ protected function hasNewPath()

	protected function getNewPath(SiteVideoMedia $media, $shortname)
	{
		return sprintf(
			'%s/%s/full/%s',
			$this->getFileBase(),
			$media->id,
			$this->getNewFilename($media, $shortname)
		);
	}

	// }}}
	// {{{ protected function hasFile()

	protected function hasFile($path)
	{
		return is_file($path);
	}

	// }}}
	// {{{ protected function moveFile()

	protected function moveFile(SiteVideoMedia $media, $old_path, $new_path)
	{
		copy($old_path, $new_path);
	}

	// }}}
}

?>
