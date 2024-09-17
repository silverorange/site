<?php

/**
 * Merge Search page for Accounts.
 *
 * @copyright 2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountMerge extends AdminSearch
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var SiteAccount
     */
    protected $account;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->mapClassPrefixToPath('Site', 'Site');
        $this->ui->loadFromXML($this->getUiXml());

        $this->id = SiteApplication::initVar('id');
        $this->getAccount();
    }

    protected function getUiXml()
    {
        return __DIR__ . '/merge.xml';
    }

    // process phase

    protected function processInternal()
    {
        parent::processInternal();

        $pager = $this->ui->getWidget('pager');
        $pager->process();
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $view = $this->ui->getWidget('index_view');

        if ($view->hasColumn('instance')
            && $this->ui->hasWidget('search_instance')) {
            $view->getColumn('instance')->visible =
                ($this->ui->getWidget('search_instance')->value === null)
                && $this->ui->getWidget('search_instance')->parent->visible;
        }

        $form = $this->ui->getWidget('search_form');
        $form->addHiddenField('id', $this->id);

        $table_view = $this->ui->getWidget('index_view');
        $link_renderer = $table_view
            ->getColumn('fullname')
            ->getRenderer('link_renderer');

        $link_renderer->link = sprintf(
            'Account/MergeSummary?id=%s&id2=%%s',
            $this->id
        );

        $this->buildAccountDetails();
    }

    protected function getAccountDetailsStore()
    {
        $account = $this->getAccount();
        $ds = new SwatDetailsStore($account);
        $ds->fullname = $account->getFullname();

        return $ds;
    }

    protected function getAccount()
    {
        $account_class = SwatDBClassMap::get(SiteAccount::class);

        $this->account = new $account_class();
        $this->account->setDatabase($this->app->db);

        if (!$this->account->load($this->id)) {
            throw new AdminNotFoundException(sprintf(
                Site::_('An account with an id of ‘%d’ does not exist.'),
                $this->id
            ));
        }

        $instance_id = $this->app->getInstanceId();
        if (
            $instance_id !== null
            && $this->account->instance->id !== $instance_id
        ) {
            throw new AdminNotFoundException(sprintf(
                Site::_('Incorrect instance for account ‘%d’.'),
                $this->id
            ));
        }

        return $this->account;
    }

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

    protected function getDetailsStore(SiteAccount $account)
    {
        return new SwatDetailsStore($account);
    }

    protected function buildNavBar()
    {
        $this->navbar->createEntry(
            $this->account->fullname,
            sprintf('Account/Details?id=%s', $this->id)
        );
        $this->navbar->createEntry(Site::_('Merge'));
        $this->title = Site::_('Merge');
    }

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

        $accounts = SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get(SiteAccountWrapper::class)
        );

        if (count($accounts) > 0) {
            $this->ui->getWidget('results_message')->content =
                $pager->getResultsMessage('result', 'results');
        }

        $class_name = SwatDBClassMap::get(SiteAccount::class);
        $store = new SwatTableStore();
        foreach ($accounts as $account) {
            $store->add($this->getDetailsStore($account));
        }

        return $store;
    }

    protected function getSQL()
    {
        return 'select Account.id, Account.fullname,
			Account.email, Account.createdate
			from Account
			%s
			where %s
			order by %s';
    }

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

    protected function getAccountSearch()
    {
        static $search = null;

        if ($search === null) {
            $search = new SiteAccountSearch($this->app, $this->ui);
        }

        return $search;
    }
}
