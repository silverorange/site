<?php

/**
 * A recordset wrapper class for SiteComment objects.
 *
 * @copyright 2008-2016 silverorange
 *
 * @see       SiteComment
 *
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteCommentWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get(SiteComment::class);
        $this->index_field = 'id';
    }
}
