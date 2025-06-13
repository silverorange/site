<?php

/**
 * A video-specific media set object.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property ?string $skin
 */
class SiteVideoMediaSet extends SiteMediaSet
{
    public $skin;

    // loader methods

    protected function getMediaEncodingWrapperClass()
    {
        return SwatDBClassMap::get(SiteVideoMediaEncodingWrapper::class);
    }

    protected function getMediaEncodingOrderBy()
    {
        return 'width desc';
    }
}
