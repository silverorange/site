<?php

require_once 'Site/dataobjects/SiteImageWrapper.php';

/**
 * An recordset wrapper class for SiteImage objects that doesn't automatically
 * load dimension bindings
 *
 * {@link SiteImageWraper} when constructed from a database result automatically
 * efficiently loads the dimension bindings for all images. This wrapper
 * leaves the dimension bindings unloaded, allowing them to be loaded later
 * if and when they are needed.
 *
 * @package   Site
 * @copyright 2010-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteImage
 * @see       SiteImageWrapper
 */
class SiteImageLazyWrapper extends SiteImageWrapper
{
	// {{{ public function __construct()

	/**
	 * Creates a new recordset wrapper
	 *
	 * @param MDB2_Result $recordset optional. The MDB2 recordset to wrap.
	 */
	public function __construct($recordset = null)
	{
		// skip SiteImageWrapper's constructor that pre-loads dimensnions
		SwatDBRecordsetWrapper::__construct($recordset);
	}

	// }}}
}

?>
