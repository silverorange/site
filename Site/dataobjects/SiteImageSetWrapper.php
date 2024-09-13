<?php

/**
 * A recordset wrapper class for SiteImageSet objects.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SiteImageSet
 */
class SiteImageSetWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get(SiteImageSet::class);
        $this->index_field = 'id';
    }
}
