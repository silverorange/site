<?php

/**
 * Index page for Accounts
 *
 * @package   Site
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountIndex extends AdminSearch
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Site', 'Site');
		$this->ui->loadFromXML($this->getSearchXml());
		$this->ui->loadFromXML($this->getUiXml());

		if ($this->app->isMultipleInstanceAdmin() &&
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
	// {{{ protected function getSearchXml()

	protected function getSearchXml()
	{
		return __DIR__.'/search.xml';
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/index.xml';
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatView $view, SwatActions $actions)
	{
		$num = count($view->getSelection());

		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Account/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}
	}

	// }}}
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
				'select count(1) from Account %s where %s',
				$search->getJoinClause(),
				$this->getWhereClause()
			)
		);

		$sql = sprintf(
			$this->getSQL(),
			$search->getJoinClause(),
			$this->getWhereClause(),
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
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$search = $this->getAccountSearch();

		return sprintf(
			'delete_date %s %s and %s',
			SwatDB::equalityOperator(null),
			$this->app->db->quote(null, 'date'),
			$search->getWhereClause()
		);
	}

	// }}}
	// {{{ protected function getAccountSearch()

	protected function getAccountSearch()
	{
		static $search = null;

		if ($search === null) {
			$search = new SiteAccountSearch($this->app, $this->ui);
		}

		return $search;
	}

	// }}}
}

?>
