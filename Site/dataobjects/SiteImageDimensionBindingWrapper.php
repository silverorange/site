<?php

/**
 * A recordset wrapper class for SiteImageDimensionBinding objects.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SiteImageDimensionBinding
 */
class SiteImageDimensionBindingWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();

        $this->index_field = 'dimension';
        $this->row_wrapper_class = $this->getImageDimensionBindingClassName();
    }

    protected function getImageDimensionBindingClassName()
    {
        return SwatDBClassMap::get(SiteImageDimensionBinding::class);
    }
}
