<?php

/**
 * Interface that defines some basic operations you can preform to on a CDN.
 *
 * @package   Site
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
interface SiteCDN
{
	// {{{ public function copyToCDN()

	public function copyToCDN($source, $destination, $mime_type = null);

	// }}}
	// {{{ public function deleteFromCDN()

	public function deleteFromCDN($file_path, $mime_type = null);

	// }}}
}

?>
