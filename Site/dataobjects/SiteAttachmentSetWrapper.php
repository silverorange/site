<?php

/**
 * A recordset wrapper class for SiteAttachmentSet objects
 *
 * @package   Site
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAttachmentSet
 */
class SiteAttachmentSetWrapper extends SwatDBRecordsetWrapper
{


	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get(SiteAttachmentSet::class);
		$this->index_field = 'id';
	}


}

?>
