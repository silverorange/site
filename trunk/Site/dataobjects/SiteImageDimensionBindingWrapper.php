<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteImageDimensionBinding.php';

/**
 * A recordset wrapper class for SiteImageDimensionBinding objects
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteImageDimensionBinding
 */
class SiteImageDimensionBindingWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->index_field = 'dimension';
		$this->row_wrapper_class = $this->getImageDimensionBindingClassName();
	}

	// }}}
	// {{{ protected function getImageDimensionBindingClassName()

	protected function getImageDimensionBindingClassName()
	{
		return SwatDBClassMap::get('SiteImageDimensionBinding');
	}

	// }}}
}

?>
