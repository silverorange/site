<?php

/**
 * @copyright 2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAtomTemplate extends SiteAbstractTemplate
{
    public function display(SiteLayoutData $data)
    {
        // Set content type to application/atom+xml
        header('Content-type: application/atom+xml; charset=utf-8');

        echo $data->content;
    }
}
