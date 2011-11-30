<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Site/dataobjects/SiteAccountWrapper.php';

/**
 * Index page for Accounts
 *
 * @package   Site
 * @copyright 2006-2011 silverorange
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
		$pager->total_records = SwatDB::queryOne($this->app->db,
			sprintf('select count(id) from Account where %s',
				$this->getWhereClause()));

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
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		/**
		 * The only way an account fullname can be null is if we've cleared the
		 * data from it with the privacy scripts - we don't ever want to display
		 * these accounts in the search results
		 */
		$where = 'Account.fullname is not null';

		foreach ($this->getWhereClauses() as $clause)
			$where.= $clause->getClause($this->app->db);

		return $where;
	}

	// }}}
	// {{{ protected function getWhereClauses()

	protected function getWhereClauses()
	{
		$clauses = array();

		// instance
		$instance_id = $this->app->getInstanceId();
		if ($instance_id === null && $this->ui->hasWidget('search_instance'))
			$instance_id = $this->ui->getWidget('search_instance')->value;

		if ($instance_id !== null) {
			$clause = new AdminSearchClause('integer:instance');
			$clause->table = 'Account';
			$clause->value = $instance_id;
			$clauses['instance'] = $clause;
		}

		// fullname
		$clause = new AdminSearchClause('fullname');
		$clause->table = 'Account';
		$clause->value = $this->ui->getWidget('search_fullname')->value;
		$clause->operator = AdminSearchClause::OP_CONTAINS;
		$clauses['fullname'] = $clause;

		// email
		$clause = new AdminSearchClause('email');
		$clause->table = 'Account';
		$clause->value = $this->ui->getWidget('search_email')->value;
		$clause->operator = AdminSearchClause::OP_CONTAINS;
		$clauses['email'] = $clause;

		return $clauses;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$pager = $this->ui->getWidget('pager');

		$sql = $this->getSQL();
		$sql = sprintf($sql,
			$this->getWhereClause(),
			$this->getOrderByClause($view, $this->getDefaultOrderBy()));

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
	// {{{ protected function getDefaultOrderBy()

	protected function getDefaultOrderBy()
	{
		return 'fullname, email';
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
			where %s
			order by %s';
	}

	// }}}
}

?>
