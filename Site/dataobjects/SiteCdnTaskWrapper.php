<?php

/**
 * A recordset wrapper class for SiteCdnTask objects.
 *
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SiteCdnTask
 */
class SiteCdnTaskWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();

        $this->row_wrapper_class = SwatDBClassMap::get(SiteCdnTask::class);
        $this->index_field = 'id';
    }
}
