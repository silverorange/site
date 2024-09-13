<?php

/**
 * @copyright 2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSMILTemplate extends SiteAbstractTemplate
{
    public function display(SiteLayoutData $data)
    {
        header('Content-Type: application/smil; charset=utf-8');

        echo $data->content;
    }
}
