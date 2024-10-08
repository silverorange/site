<?php

/**
 * A simple recordset wrapper class for SiteMedia objects that doesn't
 * do any pre-loading of sub-dataobjects.
 *
 * This is useful for cases where you just want the basic media data
 *
 * @copyright 2012-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SiteMedia
 */
class SiteSimpleMediaWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();

        $this->row_wrapper_class =
            SwatDBClassMap::get(SiteMedia::class);

        $this->index_field = 'id';
    }
}
