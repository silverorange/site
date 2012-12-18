<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';

/**
 * Delete confirmation page for Accounts
 *
 * @package   Site
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountDelete extends SiteAccountDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = $this->getDeleteSql();
		$num = SwatDB::exec($this->app->db, $sql);

		$locale = SwatI18NLocale::get($this->app->getLocale());

		$message = new SwatMessage(
			sprintf(
				Site::ngettext(
					'One account has been deleted.',
					'%s accounts have been deleted.',
					$num
				),
				$locale->formatNumber($num)
			),
			'notice'
		);

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function getDeleteSql()

	protected function getDeleteSql()
	{
		$item_list = $this->getItemList('integer');

		$now = new SwatDate();
		$now->toUTC();

		return sprintf(
			'update Account set delete_date = %s where id in (%s)',
			$this->app->db->quote($now, 'date'),
			$item_list
		);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$dep = $this->getDependencies();

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
	// {{{ protected function getDependencies()

	protected function getDependencies()
	{
		$item_list = $this->getItemList('integer');

		$dep = new AdminListDependency();
		$dep->setTitle(
			Site::_('account'),
			Site::_('accounts')
		);

		$dep->entries = AdminListDependency::queryEntries(
			$this->app->db,
			'Account', 'integer:id', null, 'text:title', 'id',
			'id in ('.$item_list.')', AdminDependency::DELETE
		);

		return $dep;
	}

	// }}}
}

?>
