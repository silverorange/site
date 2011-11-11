<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteAttachmentSet.php';

/**
 * A recordset wrapper class for SiteAttachmentSet objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAttachmentSet
 */
class SiteAttachmentSetWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('SiteAttachmentSet');
		$this->index_field = 'id';
	}

	// }}}
}

?>
