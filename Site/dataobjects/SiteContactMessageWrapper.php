<?php

/**
 * A recordset wrapper class for SiteContactMessage objects.
 *
 * @copyright 2010-2016 silverorange
 *
 * @see       SiteContactMessage
 *
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteContactMessageWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get(SiteContactMessage::class);
        $this->index_field = 'id';
    }
}
