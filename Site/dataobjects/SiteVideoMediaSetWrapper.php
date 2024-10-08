<?php

/**
 * A recordset wrapper class for SiteVideoMediaSet objects.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SiteVideoMediaSet
 */
class SiteVideoMediaSetWrapper extends SiteMediaSetWrapper
{
    protected function getMediaEncodingWrapperClass()
    {
        return SwatDBClassMap::get(SiteVideoMediaEncodingWrapper::class);
    }

    protected function getMediaEncodingOrderBy()
    {
        return 'media_set, width desc';
    }

    protected function init()
    {
        parent::init();

        $this->row_wrapper_class = SwatDBClassMap::get(SiteVideoMediaSet::class);
    }
}
