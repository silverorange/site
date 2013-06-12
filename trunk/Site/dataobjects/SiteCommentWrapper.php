<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteComment.php';

/**
 * A recordset wrapper class for SiteComment objects
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @see       SiteComment
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteCommentWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('SiteComment');
		$this->index_field = 'id';
	}

	// }}}
}

?>
