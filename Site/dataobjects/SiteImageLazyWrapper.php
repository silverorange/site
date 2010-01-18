<?php

require_once 'Site/dataobjects/SiteImageWrapper.php';

/**
 * An efficient recordset wrapper class for SiteImage objects
 *
 * Allows loading images with no dimension information, and loading
 * specific dimensions rather than all dimensions.
 *
 * @package   Site
 * @copyright 2010 silverorange
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
	// {{{ public function loadDimensions()

	/**
	 * Load image dimensions
	 *
	 * @param array Dimension shortnames to load
	 */
	public function loadDimensions(array $dimensions)
	{
		$this->attachDimensionBindings($dimensions);
	}

	// }}}
}

?>
