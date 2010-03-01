<?php

require_once 'Site/exceptions/SiteNotFoundException.php';

/**
 * Thrown when the path we're looking up is longer that 254 characters
 *
 * @package   Site
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SitePathTooLongException extends SiteNotFoundException
{
}

?>
