<?php

/**
 * A recordset wrapper class for SiteAccountLoginHistory objects.
 *
 * @copyright 2011-2016 silverorange
 *
 * @see       SiteAccountLoginHistory
 */
class SiteAccountLoginHistoryWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();

        $this->row_wrapper_class =
            SwatDBClassMap::get(SiteAccountLoginHistory::class);

        $this->index_field = 'id';
    }
}
