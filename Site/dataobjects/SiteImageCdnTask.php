<?php

/**
 * A task that should be performed on a CDN in the near future.
 *
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property ?SiteImage          $image
 * @property ?SiteImageDimension $dimension
 */
class SiteImageCdnTask extends SiteCdnTask
{
    // public methods

    public function getAttemptDescription()
    {
        return match ($this->operation) {
            'copy', 'update' => sprintf(
                Site::_('Updating the dimension ‘%s’ of image ‘%s’ ... '),
                $this->dimension->shortname,
                $this->image->id
            ),
            default => sprintf(
                $this->getAttemptDescriptionString(),
                Site::_('image'),
                $this->getInternalValue('image'),
                $this->file_path,
                $this->operation
            )
        };
    }

    // protected methods

    protected function init()
    {
        parent::init();

        $this->registerInternalProperty(
            'image',
            SwatDBClassMap::get(SiteImage::class)
        );

        $this->registerInternalProperty(
            'dimension',
            SwatDBClassMap::get(SiteImageDimension::class)
        );

        $this->table = 'ImageCdnQueue';
    }

    protected function getLocalFilePath()
    {
        return ($this->hasImageAndDimension()) ?
            $this->image->getFilePath($this->dimension->shortname) :
            null;
    }

    protected function copy(SiteCdnModule $cdn)
    {
        if ($this->hasImageAndDimension()) {
            $shortname = $this->dimension->shortname;

            // Perform all DB actions first. That way we can roll them back if
            // anything goes wrong with the CDN operation.
            $this->image->setOnCdn(true, $shortname);

            $headers = $this->image->getHttpHeaders($shortname);

            if (mb_strlen($this->override_http_headers)) {
                $headers = array_merge(
                    $headers,
                    unserialize($this->override_http_headers)
                );
            }

            $cdn->copyFile(
                $this->image->getUriSuffix($shortname),
                $this->image->getFilePath($shortname),
                $headers,
                $this->getAccessType()
            );
        }
    }

    protected function remove(SiteCdnModule $cdn)
    {
        // Perform all DB actions first. That way we can roll them back if
        // anything goes wrong with the CDN operation.
        if ($this->hasImageAndDimension()) {
            $this->image->setOnCdn(false, $this->dimension->shortname);
        }

        $cdn->removeFile(
            $this->file_path
        );
    }

    // helper methods

    protected function hasImageAndDimension()
    {
        return ($this->image instanceof SiteImage)
            && ($this->dimension instanceof SiteImageDimension);
    }

    protected function getAccessType()
    {
        return 'public';
    }
}
