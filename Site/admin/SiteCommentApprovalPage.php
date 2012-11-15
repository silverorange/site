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
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->getWidget('delete_button')->title = Site::_('Deny');
	}

	// }}}

	// {{{ protected function approve()

	protected function approve()
	{
		$this->data_object->setStatus(SiteComment::STATUS_PUBLISHED,
			$this->app);

		$this->data_object->save();
	}

	// }}}
	// {{{ protected function delete()

	protected function delete()
	{
		$this->data_object->setStatus(SiteComment::STATUS_UNPUBLISHED,
			$this->app);

		$this->data_object->save();
	}

	// }}}
}

?>
