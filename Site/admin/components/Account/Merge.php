<?php

/**
 * Merge Search page for Accounts
 *
 * @package   Site
 * @copyright 2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountMerge extends AdminSearch
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var SiteAccount
	 */
	protected $account;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Site', 'Site');
		$this->ui->loadFromXML($this->getUiXml());

		$this->id = SiteApplication::initVar('id');
		$this->getAccount();

		$form = $this->ui->getWidget('search_form');
		$form->addHiddenField('id', $this->id);

		$table_view = $this->ui->getWidget('index_view');
		$link_renderer = $table_view
			->getColumn('fullname')
			->getRenderer('link_renderer');

		$link_renderer->link = sprintf(
			'Account/MergeSummary?id=%s&amp;id2=%%s',
			$this->id
		);
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/admin/components/Account/merge.xml';
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

		$this->buildAccountDetails();

		$view = $this->ui->getWidget('index_view');

		if ($view->hasColumn('instance') &&
			$this->ui->hasWidget('search_instance')) {
			$view->getColumn('instance')->visible =
				($this->ui->getWidget('search_instance')->value === null) &&
				$this->ui->getWidget('search_instance')->parent->visible;
		}
	}

	// }}}
	// {{{ protected function getAccountDetailsStore()

	protected function getAccountDetailsStore()
	{
		$account = $this->getAccount();
		$ds = new SwatDetailsStore($account);
		$ds->fullname = $account->getFullname();
		return $ds;
	}

	// }}}
	// {{{ protected function getAccount()

	protected function getAccount()
	{
		if ($this->account === null) {
			$account_class = SwatDBClassMap::get('SiteAccount');

			$this->account = new $account_class();
			$this->account->setDatabase($this->app->db);

			if (!$this->account->load($this->id)) {
				throw new AdminNotFoundException(sprintf(
					Site::_('An account with an id of ‘%d’ does not exist.'),
					$this->id
				));
			}

			$instance_id = $this->app->getInstanceId();
			if ($instance_id !== null) {
				if ($this->account->instance->id !== $instance_id) {
					throw new AdminNotFoundException(sprintf(
						Store::_('Incorrect instance for account ‘%d’.'),
						$this->id
					));
				}
			}
		}

		return $this->account;
	}

	// }}}
	// {{{ protected function buildAccountDetails()

	protected function buildAccountDetails()
	{
		$ds = $this->getAccountDetailsStore();

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = Site::_('Merge Account');
		$details_frame->subtitle = $ds->fullname;

		$details_view = $this->ui->getWidget('details_view');

		$date_field = $details_view->getField('createdate');
		$date_renderer = $date_field->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;

		$details_view->data = $ds;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->navbar->addEntry(new SwatNavBarEntry(
			$this->account->fullname,
			sprintf('Account/Details?id=%s', $this->id)
		));
		$this->navbar->addEntry(new SwatNavBarEntry(Site::_('Merge')));
		$this->title = Site::_('Merge');
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
	// {{{ protected function getSQL()

	protected function getSQL()
	{
		return 'select Account.id, Account.fullname,
			Account.email, Account.createdate
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
			'delete_date %s %s and id != %s and %s',
			SwatDB::equalityOperator(null),
			$this->app->db->quote(null, 'date'),
			$this->id,
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
