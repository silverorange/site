<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Site/dataobjects/SiteAccountWrapper.php';
require_once 'Site/admin/SiteAccountSearch.php';

/**
 * Index page for Accounts
 *
 * @package   Site
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountIndex extends AdminSearch
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/admin/components/Account/index.xml';

	/**
	 * @var string
	 */
	protected $search_xml = 'Site/admin/components/Account/search.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Site', 'Site');
		$this->ui->loadFromXML($this->search_xml);
		$this->ui->loadFromXML($this->ui_xml);

		if ($this->app->getInstance() === null &&
			$this->ui->hasWidget('search_instance')) {

			$search_instance = $this->ui->getWidget('search_instance');
			$search_instance->show_blank = true;
			$options = SwatDB::getOptionArray($this->app->db,
				'Instance', 'title', 'id', 'title');

			if (count($options) > 1) {
				$search_instance->addOptionsByArray($options);
				$search_instance->parent->visible = true;
			}
		}
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

		if ($view->hasColumn('instance') &&
			$this->ui->hasWidget('search_instance')) {

			$view->getColumn('instance')->visible =
				($this->ui->getWidget('search_instance')->value === null) &&
				$this->ui->getWidget('search_instance')->parent->visible;
		}
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$search = $this->getAccountSearch();

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne(
			$this->app->db,
			sprintf(
				'select count(id) from Account where %s',
				$search->getWhereClause()
			)
		);

		$sql = sprintf(
			$this->getSQL(),
			$search->getJoinClause(),
			$search->getWhereClause(),
			$this->getOrderByClause($view, $search->getOrderByClause())
		);

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$accounts = SwatDB::query($this->app->db, $sql);

		if (count($accounts) > 0) {
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage('result', 'results');
		}

		$class_name = SwatDBClassMap::get('SiteAccount');
		$store = new SwatTableStore();
		foreach ($accounts as $row) {
			if ($row instanceof SiteAccount) {
				$account = $row;
			} else {
				$account = new $class_name($row);
				$account->setDatabase($this->app->db);
			}
			$store->add($this->getDetailsStore($account, $row));
		}

		return $store;
	}

	// }}}
	// {{{ protected function getDetailsStore()

	protected function getDetailsStore(SiteAccount $account, $row)
	{
		$ds = new SwatDetailsStore($account);
		$ds->fullname = $account->getFullname();
		return $ds;
	}

	// }}}
	// {{{ protected function getSQL()

	protected function getSQL()
	{
		return 'select Account.id, Account.fullname,
			Account.email, Account.instance
			from Account
			%s
			where %s
			order by %s';
	}

	// }}}
	// {{{ protected function getAccountSearch()

	protected function getAccountSearch()
	{
		return new SiteAccountSearch($this->app, $this->ui);
	}

	// }}}
}

?>
