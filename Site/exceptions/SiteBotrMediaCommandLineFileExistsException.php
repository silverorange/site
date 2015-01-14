<?php

require_once 'Site/exceptions/SiteException.php';

/**
 * Exception for SiteBotrMediaCommandLine applications when trying to download
 * to a file that already exists on disk.
 *
 * @package   Site
 * @copyright 2013-2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaCommandLineFileExistsException
	extends SiteBotrMediaCommandLineException
{
}

?>
