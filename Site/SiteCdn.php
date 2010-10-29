<?php

/**
 * Interface that defines some basic operations that can be preformed on a CDN.
 *
 * @package   Site
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
interface SiteCdn
{
	// {{{ public function copyFile()

	/**
	 * Copies a file to the CDN
	 *
	 * @param string $source the source path of the file to copy.
	 * @param string $destination the destination path of the file to copy.
	 * @param string $mime_type the MIME type of the file. Defaults to null.
	 */
	public function copyFile($source, $destination, $mime_type = null);

	// }}}
	// {{{ public function deleteFile()

	/**
	 * Deletes a file from the CDN
	 *
	 * @param string $file_path the path, on the CDN, of the file to delete.
	 */
	public function deleteFile($file_path);

	// }}}
}

?>
