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
 * @copyright 2012-2013 silverorange
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
		$where_clause = sprintf(
			'Account.delete_date %s %s',
			SwatDB::equalityOperator(null),
			$this->app->db->quote(null, 'date')
		);

		if ($this->app->getInstance() !== null) {
			$where_clause = sprintf(
				' and Account.instance = %s',
				$this->app->db->quote(
					$this->app->getInstanceId(),
					'integer'
				)
			);
		}

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne(
			$this->app->db,
			sprintf(
				'select count(id) from Account
				inner join SuspiciousAccountView on
					SuspiciousAccountView.account = Account.id
				where %s',
				$where_clause
			)
		);

		$sql = sprintf(
			'select * from Account
			inner join SuspiciousAccountView on
				SuspiciousAccountView.account = Account.id
			where %s
			order by %s',
			$where_clause,
			$this->getOrderByClause($view, 'Account.id')
		);

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$rows = SwatDB::query($this->app->db, $sql);

		$class_name = SwatDBClassMap::get('SiteAccount');
		$store = new SwatTableStore();

		foreach ($rows as $row) {
			$account = new $class_name($row);
			$account->setDatabase($this->app->db);

			$ds = new SwatDetailsStore($account);
			$ds->fullname = $account->getFullName();
			$ds->details  = SiteAccount::getSuspiciousActivitySummary($row);

			$store->add($ds);
		}

		return $store;
	}

	// }}}
}

?>
