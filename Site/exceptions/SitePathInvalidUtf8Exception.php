<?php

require_once 'Site/exceptions/SiteException.php';

/**
 * Thrown when the path we're looking up has invalid UFF-8 in it.
 *
 * @package   Site
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SitePathInvalidUtf8Exception extends SiteException
{
}

?>
