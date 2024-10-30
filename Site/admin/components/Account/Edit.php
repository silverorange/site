<?php

/**
 * Edit page for Accounts.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountEdit extends AdminDBEdit
{
    protected $account;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->initAccount();

        $this->ui->mapClassPrefixToPath('Site', 'Site');
        $this->ui->loadFromXML($this->getUiXml());
    }

    protected function initAccount()
    {
        $account_class = SwatDBClassMap::get(SiteAccount::class);

        $this->account = new $account_class();
        $this->account->setDatabase($this->app->db);

        if ($this->id !== null) {
            if (!$this->account->load($this->id)) {
                throw new AdminNotFoundException(sprintf(
                    Site::_('A account with an id of ‘%d’ does not exist.'),
                    $this->id
                ));
            }

            $instance_id = $this->app->getInstanceId();
            if ($instance_id !== null) {
                if ($this->account->instance->id !== $instance_id) {
                    throw new AdminNotFoundException(sprintf(Store::_(
                        'Incorrect instance for account ‘%d’.'
                    ), $this->id));
                }
            }
        } elseif ($this->app->hasModule('SiteMultipleInstanceModule')) {
            $this->account->instance = $this->app->instance->getInstance();
        }
    }

    protected function getUiXml()
    {
        return __DIR__ . '/edit.xml';
    }

    // process phase

    protected function validate(): void
    {
        $email = $this->ui->getWidget('email');
        if ($email->hasMessage()) {
            return;
        }

        $instance = ($this->app->hasModule('SiteMultipleInstanceModule')) ?
            $this->app->instance->getInstance() : null;

        $class_name = SwatDBClassMap::get(SiteAccount::class);
        $account = new $class_name();
        $account->setDatabase($this->app->db);
        $found = $account->loadWithEmail($email->value, $instance);

        if ($found && $this->account->id !== $account->id) {
            $message = new SwatMessage(
                Site::_('An account already exists with this email address.'),
                'error'
            );

            $message->content_type = 'text/xml';
            $email->addMessage($message);
        }
    }

    protected function saveDBData()
    {
        $this->updateAccount();

        if ($this->id === null) {
            $now = new SwatDate();
            $now->toUTC();
            $this->account->createdate = $now;
        }

        if ($this->account->isModified() && $this->id !== null) {
            $this->account->setDirty();
        }

        $this->account->save();

        $this->updateBindings();

        $this->app->messages->add($this->getUpdateMessage());
    }

    protected function updateAccount()
    {
        $this->account->email = $this->ui->getWidget('email')->value;
        $this->account->fullname = $this->ui->getWidget('fullname')->value;
    }

    protected function updateBindings() {}

    protected function getUpdateMessage()
    {
        return new SwatMessage(sprintf(
            Site::_('Account “%s” has been saved.'),
            $this->account->getFullname()
        ));
    }

    protected function relocate()
    {
        $this->app->relocate('Account/Details?id=' . $this->account->id);
    }

    // build phase

    protected function loadDBData()
    {
        $this->ui->setValues($this->account->getAttributes());
    }

    protected function buildNavBar()
    {
        if ($this->id === null) {
            $this->navbar->addEntry(new SwatNavBarEntry(Site::_('New Account')));
            $this->title = Site::_('New Account');
        } else {
            $this->navbar->addEntry(new SwatNavBarEntry(
                $this->account->getFullname(),
                sprintf('Account/Details?id=%s', $this->id)
            ));
            $this->navbar->addEntry(new SwatNavBarEntry(Site::_('Edit')));
            $this->title = $this->account->getFullname();
        }
    }
}
