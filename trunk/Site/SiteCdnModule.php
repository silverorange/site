<?php

require_once 'SiteApplicationModule.php';

/**
 * Base class for application modules that provide access to a CDN
 *
 * @package   Site
 * @copyright 2011-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteCdnModule extends SiteApplicationModule
{
	// {{{ abstract public function copyFile()

	/**
	 * Copies a file to the CDN
	 *
	 * @param string $filename the name of the file to update.
	 * @param string $source the local source of the file.
	 * @param array $headers an array of headers associated with the file.
	 * @param string $access_type the access type, public/private, of the file.
	 */
	abstract public function copyFile($filename, $source, $headers,
		$access_type);

	// }}}
	// {{{ abstract public function removeFile()

	/**
	 * Removes a file from the CDN
	 *
	 * @param string $filename the name of the file to delete.
	 */
	abstract public function removeFile($filename);

	// }}}
	// {{{ abstract public function getUri()

	/**
	 * Gets a URI for a file on the CDN 
	 *
	 * @param string $filename the name of the file.
	 * @param string $expires expiration time expressed either as a number
	 *                        of seconds since UNIX Epoch, or any string
	 *                        that strtotime() can understand 
	 */
	abstract public function getUri($filename, $expires = null);

	// }}}
	// {{{ abstract public function getStreamingUri()

	/**
	 * Gets a uri for a file on the CDN 
	 *
	 * @param string $filename the name of the file.
	 * @param string $expires expiration time expressed either as a number
	 *                        of seconds since UNIX Epoch, or any string
	 *                        that strtotime() can understand 
	 */
	abstract public function getStreamingUri($filename, $expires = null);

	// }}}
}

?>
