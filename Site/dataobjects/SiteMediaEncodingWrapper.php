<?php

/**
 * A recordset wrapper class for MediaEncoding objects.
 *
 * @copyright 2011-2016 silverorange
 *
 * @see       SiteMediaEncoding
 *
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaEncodingWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();

        $this->row_wrapper_class =
            SwatDBClassMap::get(SiteMediaEncoding::class);

        $this->index_field = 'id';
    }
}
