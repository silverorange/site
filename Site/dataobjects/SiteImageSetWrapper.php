<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteImageSet.php';

/**
 * A recordset wrapper class for SiteImageSet objects
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteImageSet
 */
class SiteImageSetWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('SiteImageSet');
		$this->index_field = 'integer:id';
	}

	// }}}
}

?>
