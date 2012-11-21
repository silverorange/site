<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Site/dataobjects/SiteAccountWrapper.php';

/**
 * Index page for Suspicious Accounts
 *
 * @package   Site
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountSuspicious extends AdminIndex
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/admin/components/Account/suspicious.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Site', 'Site');
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$pager = $this->ui->getWidget('pager');
		$pager->process();
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$view = $this->ui->getWidget('index_view');

		if ($view->hasColumn('instance')) {
			$view->getColumn('instance')->visible =
				($this->app->getInstance() === null &&
					$this->app->hasModule('SiteMultipleInstanceModule'));
		}
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		if ($this->app->getInstance() !== null) {
			$where_clause = sprintf('Account.instance = %s',
				$this->app->db->quote($this->app->getInstanceId(),
					'integer'));
		} else {
			$where_clause = '1 = 1';
		}

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne(
			$this->app->db,
			sprintf('select count(id) from Account
				inner join SuspiciousAccountView on
					SuspiciousAccountView.account = Account.id
				where %s',
				$where_clause));

		$sql = sprintf(
			'select Account.*
			from Account
			inner join SuspiciousAccountView on
				SuspiciousAccountView.account = Account.id
			where %s
			order by %s',
			$where_clause,
			$this->getOrderByClause($view, 'Account.id'));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$accounts = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('SiteAccountWrapper'));

		$store = new SwatTableStore();
		foreach ($accounts as $account) {
			$store->add($this->getDetailsStore($account));
		}

		return $store;
	}

	// }}}
	// {{{ protected function getDetailsStore()

	protected function getDetailsStore(SiteAccount $account)
	{
		$ds = new SwatDetailsStore($account);
		$ds->fullname = $account->getFullname();
		return $ds;
	}

	// }}}
}

?>
