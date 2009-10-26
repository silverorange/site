<?php

require_once 'Admin/pages/AdminApproval.php';
require_once 'Site/dataobjects/SiteComment.php';

/**
 * Abstract approval page
 *
 * @package   Site
 * @copyright 2008-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteCommentApprovalPage extends AdminApproval
{
	// {{{ protected function approve()

	protected function approve()
	{
		$this->data_object->status = SiteComment::STATUS_PUBLISHED;
		$this->data_object->save();
	}

	// }}}
}

?>
