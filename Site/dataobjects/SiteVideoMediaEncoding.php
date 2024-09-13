<?php

/**
 * A video-specific media encoding object.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteVideoMediaEncoding extends SiteMediaEncoding
{
    /**
     * Width in pixels.
     *
     * @var int
     */
    public $width;

    protected function init()
    {
        parent::init();

        $this->registerInternalProperty(
            'media_set',
            SwatDBClassMap::get(SiteVideoMediaSet::class)
        );
    }
}
