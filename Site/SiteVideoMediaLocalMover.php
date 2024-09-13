<?php

/**
 * Application to copy video to the new local directory structure.
 *
 * Temporary script until we can fix our encoding process to include HLS.
 *
 * @copyright 2015-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SiteVideoMediaMover
 */
class SiteVideoMediaLocalMover extends SiteVideoMediaMover
{
    protected $file_base;

    public function setFileBase($file_base)
    {
        $this->file_base = $file_base;
    }

    public function getFileBase()
    {
        if ($this->file_base === null) {
            throw new SiteException('File base has not been set.');
        }

        return $this->file_base;
    }

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

    protected function getNewPath(SiteVideoMedia $media, $shortname)
    {
        return sprintf(
            '%s/%s/full/%s',
            $this->getFileBase(),
            $media->id,
            $this->getNewFilename($media, $shortname)
        );
    }

    protected function hasFile($path)
    {
        return is_file($path);
    }

    protected function moveFile(SiteVideoMedia $media, $old_path, $new_path)
    {
        $parts = pathinfo($new_path);
        $directory = $parts['dirname'];

        if (!file_exists($directory)) {
            mkdir($directory, 0o777, true);
        }

        copy($old_path, $new_path);
    }

    protected function cleanUp($path)
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
