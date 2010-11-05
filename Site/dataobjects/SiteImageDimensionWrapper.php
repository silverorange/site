<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteImageDimension.php';

/**
 * A recordset wrapper class for SiteImageDimension objects
 *
 * @package   Site
 * @copyright 2008-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteImageDimension
 */
class SiteImageDimensionWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('SiteImageDimension');
		$this->index_field = 'id';
	}

	// }}}
}

?>
