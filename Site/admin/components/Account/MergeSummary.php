<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Site/dataobjects/SiteAccountWrapper.php';
require_once 'Site/admin/SiteAccountSearch.php';

/**
 * Merge Summary page for Accounts
 *
 * @package   Site
 * @copyright 2006-2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountMergeSummary extends AdminPage
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var SiteAccount
	 */
	protected $account1;

	/**
	 * @var integer
	 */
	protected $id2;

	/**
	 * @var SiteAccount
	 */
	protected $account2;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Site', 'Site');
		$this->ui->loadFromXML($this->getUiXml());

		$this->id = SiteApplication::initVar('id');
		$this->account1 = $this->getAccount($this->id);

		$this->id2 = SiteApplication::initVar('id2');
		$this->account2 = $this->getAccount($this->id2);
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/admin/components/Account/merge-summary.xml';
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$form = $this->ui->getWidget('merge_form');
		$form->process();
		if ($form->isProcessed()) {
			if ($this->ui->getWidget('cancel_button')->hasBeenClicked()) {
				$this->app->relocate(sprintf(
					'Account/Details?id=%s',
					$this->id
				));
			} elseif ($this->ui->getWidget('keep_first_button')->hasBeenClicked()) {
				$this->app->relocate(sprintf(
					'Account/MergeConfirm?id=%s&id2=%s&keep_first=1',
					$this->id,
					$this->id2
				));
			} elseif ($this->ui->getWidget('keep_second_button')->hasBeenClicked()) {
				$this->app->relocate(sprintf(
					'Account/MergeConfirm?id=%s&id2=%s&keep_first=0',
					$this->id,
					$this->id2
				));
			}
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('merge_form');
		$form->action = $this->source;
		$form->addHiddenField('id', $this->id);
		$form->addHiddenField('id2', $this->id2);

		$keep_first_button = $this->ui->getWidget('keep_first_button');
		$keep_first_button->title = sprintf(
			'Merge and keep %s',
			$this->account1->email
		);

		$keep_second_button = $this->ui->getWidget('keep_second_button');
		$keep_second_button->title = sprintf(
			'Merge and keep %s',
			$this->account2->email
		);

		$this->buildAccountDetailsFrame();
	}

	// }}}
	// {{{ protected function getAccountDetailsStore()

	protected function getAccountDetailsStore(SiteAccount $account)
	{
		$ds = new SwatDetailsStore($account);
		$ds->fullname = $account->getFullname();
		return $ds;
	}

	// }}}
	// {{{ protected function getAccount()

	protected function getAccount($id)
	{
		$account_class = SwatDBClassMap::get('SiteAccount');

		$account = new $account_class();
		$account->setDatabase($this->app->db);

		if (!$account->load($id)) {
			throw new AdminNotFoundException(sprintf(
				Site::_('An account with an id of ‘%d’ does not exist.'),
				$id
			));
		}

		$instance_id = $this->app->getInstanceId();
		if ($instance_id !== null) {
			if ($account->instance->id !== $instance_id) {
				throw new AdminNotFoundException(sprintf(
					Site::_('Incorrect instance for account ‘%d’.'),
					$id
				));
			}
		}

		return $account;
	}

	// }}}
	// {{{ protected function buildAccountDetails()

	protected function buildAccountDetailsFrame()
	{
		$ds1 = $this->getAccountDetailsStore($this->account1);
		$ds2 = $this->getAccountDetailsStore($this->account2);


		$this->buildAccountDetails(
			$ds1,
			$this->ui->getWidget('details_view_left')
		);

		$this->buildAccountDetails(
			$ds2,
			$this->ui->getWidget('details_view_right')
		);

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = Site::_('Merge Accounts');
		$details_frame->subtitle = sprintf(
			'%s, %s',
			$ds1->fullname,
			$ds2->fullname
		);
	}

	// }}}
	// {{{ protected function buildAccountDetails()

	protected function buildAccountDetails(
		SwatDetailsStore $ds,
		SwatDetailsView $details_view
	) {
		$date_field = $details_view->getField('createdate');
		$date_renderer = $date_field->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;

		$details_view->data = $ds;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->navbar->createEntry(
			$this->account1->getFullname(),
			sprintf('Account/Details?id=%s', $this->id)
		);

		$this->navbar->createEntry(
			Site::_('Merge'),
			sprintf('Account/Merge?id=%s', $this->id)
		);

		$this->navbar->createEntry(sprintf(
			Site::_('Merge With %s'),
			$this->account2->getFullname()
		));
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
			$this->app->db->quote($this->id, 'integer'),
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

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/site/admin/styles/site-account-merge.css'
		);
	}

	// }}}
}

?>
