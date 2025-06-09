<?php

/**
 * A task that should be preformed to a CDN in the near future.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property SiteMedia         $media
 * @property SiteMediaEncoding $encoding
 */
class SiteMediaCdnTask extends SiteCdnTask
{
    // public methods

    public function getAttemptDescription()
    {
        return match ($this->operation) {
            'copy', 'update' => sprintf(
                Site::_('Updating the ‘%s’ encoding of media ‘%s’ ... '),
                $this->encoding->shortname,
                $this->media->id
            ),
            default => sprintf(
                $this->getAttemptDescriptionString(),
                Site::_('media'),
                $this->getInternalValue('media'),
                $this->file_path,
                $this->operation
            ),
        };
    }

    // protected methods

    protected function init()
    {
        parent::init();

        $this->registerInternalProperty(
            'media',
            SwatDBClassMap::get(SiteMedia::class)
        );

        $this->registerInternalProperty(
            'encoding',
            SwatDBClassMap::get(SiteMediaEncoding::class)
        );

        $this->table = 'MediaCdnQueue';
    }

    protected function getLocalFilePath()
    {
        return ($this->hasMediaAndEncoding()) ?
            $this->media->getFilePath($this->encoding->shortname) :
            null;
    }

    protected function copy(SiteCdnModule $cdn)
    {
        if ($this->hasMediaAndEncoding()) {
            $shortname = $this->encoding->shortname;

            // Perform all DB actions first. That way we can roll them back if
            // anything goes wrong with the CDN operation.
            $this->media->setOnCdn(true, $shortname);

            $headers = $this->media->getHttpHeaders($shortname);

            if (mb_strlen($this->override_http_headers)) {
                $headers = array_merge(
                    $headers,
                    unserialize($this->override_http_headers)
                );
            }

            $cdn->copyFile(
                $this->media->getUriSuffix($shortname),
                $this->media->getFilePath($shortname),
                $headers,
                $this->getAccessType()
            );
        }
    }

    protected function remove(SiteCdnModule $cdn)
    {
        // Perform all DB actions first. That way we can roll them back if
        // anything goes wrong with the CDN operation.
        if ($this->hasMediaAndEncoding()) {
            $this->media->setOnCdn(false, $this->encoding->shortname);
        }

        $cdn->removeFile(
            $this->file_path
        );
    }

    // helper methods

    protected function hasMediaAndEncoding()
    {
        return ($this->media instanceof SiteMedia)
            && ($this->encoding instanceof SiteMediaEncoding);
    }

    protected function getAccessType()
    {
        return ($this->media->media_set->private)
            ? 'private'
            : 'public';
    }
}
