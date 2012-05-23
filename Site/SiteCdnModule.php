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
	 * @param string $source the source path of the file to copy.
	 * @param string $destination the destination path of the file to copy.
	 * @param string $mime_type the MIME type of the file.
	 * @param string $access_type the access type, public/private, of the file.
	 * @param array $http_headers an array of headers associated with the file.
	 * @param array $metadata an array of metadata associated with the file.
	 */
	abstract public function copyFile($source, $destination, $mime_type = null,
		$access_type = null, $http_headers = array(), $metadata = array());

	// }}}
	// {{{ abstract public function updateFileMetadata()

	/**
	 * Updates a file's metadata
	 *
	 * @param string $path the path of the file to update.
	 * @param string $mime_type the MIME type of the file.
	 * @param string $access_type the access type, public/private, of the file.
	 * @param array $http_headers an array of headers associated with the file.
	 * @param array $metadata an array of metadata associated with the file.
	 */
	abstract public function updateFileMetadata($path, $mime_type = null,
		$access_type = null, $http_headers = array(), $metadata = array());

	// }}}
	// {{{ abstract public function deleteFile()

	/**
	 * Deletes a file from the CDN
	 *
	 * @param string $file_path the path, on the CDN, of the file to delete.
	 */
	abstract public function deleteFile($file_path);

	// }}}
}

?>
