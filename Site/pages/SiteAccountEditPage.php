<?php

require_once 'Site/dataobjects/SiteAccount.php';
require_once 'Site/pages/SiteDBEditPage.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatUI.php';

/**
 * @package   Site
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountEditPage extends SiteDBEditPage
{
	// {{{ protected properties

	/**
	 * @var SiteAccount
	 */
	protected $account;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/pages/account-edit.xml';
	}

	// }}}
	// {{{ protected function createNewAccount()

	/**
	 * Creates a new account object when a new account is created
	 *
	 * @return StoreAccount the new account object.
	 */
	protected function createNewAccount()
	{
		$class_name = SwatDBClassMap::get('SiteAccount');
		$account = new $class_name();

		if ($this->app->hasModule('SiteMultipleInstanceModule'))
			$account->instance = $this->app->instance->getInstance();

		return $account;
	}

	// }}}
	// {{{ protected function isNew()

	protected function isNew(SwatForm $form)
	{
		return (!$this->app->session->isLoggedIn());
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initAccount();

		$confirm_password = $this->ui->getWidget('confirm_password');
		$confirm_password->password_widget = $this->ui->getWidget('password');

		$confirm_email = $this->ui->getWidget('confirm_email');
		$confirm_email->email_widget = $this->ui->getWidget('email');
	}

	// }}}
	// {{{ protected function initAccount()

	protected function initAccount()
	{
		if ($this->app->session->isLoggedIn()) {
			$this->account = $this->app->session->account;
		} else {
			$this->account = $this->createNewAccount();
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate(SwatForm $form)
	{
		$this->validateEmail();
	}

	// }}}
	// {{{ protected function validateEmail()

	protected function validateEmail()
	{
		$email = $this->ui->getWidget('email');
		if ($email->hasMessage())
			return;

		$instance = ($this->app->hasModule('SiteMultipleInstanceModule')) ?
			$this->app->instance->getInstance() : null;

		$class_name = SwatDBClassMap::get('SiteAccount');
		$account = new $class_name();
		$account->setDatabase($this->app->db);
		$found = $account->loadWithEmail($email->value, $instance);

		$account_id = ($this->app->session->isLoggedIn()) ?
			$this->app->session->account->id : null;

		if ($found && $account_id !== $account->id) {
			$email_link = sprintf('<a href="account/forgotpassword?email=%s">',
				$email->value);

			$message = new SwatMessage(
				Site::_('An account already exists with this email address.'),
				SwatMessage::ERROR);

			$message->secondary_content =
				sprintf(Site::_('You can %srequest a new password%s to log '.
					'into the existing account.'), $email_link, '</a>');

			$message->content_type = 'text/xml';
			$email->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function updateAccount()

	protected function updateAccount(SwatForm $form)
	{
		$this->assignUiValuesToObject($this->account, array(
			'fullname',
			'email',
		));
	}

	// }}}
	// {{{ protected function saveData()

	protected function saveData(SwatForm $form)
	{
		$this->updateAccount($form);

		if ($this->isNew($form)) {
			$this->account->setPassword(
				$this->ui->getWidget('password')->value);

			$this->account->createdate = new SwatDate();
			$this->account->createdate->toUTC();

			$this->account->setDatabase($this->app->db);
			$this->account->save();

			$this->app->session->loginById($this->account->id);

			$message = new SwatMessage(
				Site::_('New account has been created.'));

			$this->app->messages->add($message);
		} elseif ($this->account->isModified()) {
			$this->app->session->account->save();

			$message = new SwatMessage(
				Site::_('Account details have been updated.'));

			$this->app->messages->add($message);
		}
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate(SwatForm $form)
	{
		$this->app->relocate('account');
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('edit_form');
		if (!$this->isNew($form)) {
			$this->ui->getWidget('submit_button')->title =
				Site::_('Update Account Details');

			$this->ui->getWidget('password_container')->visible = false;
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$form = $this->ui->getWidget('edit_form');
		if ($this->isNew($form)) {
			$this->layout->navbar->createEntry(Site::_('Create a New Account'));
		} else {
			$this->layout->navbar->createEntry(Site::_('Edit Account Details'));
		}
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		parent::buildTitle();

		$form = $this->ui->getWidget('edit_form');
		if ($this->isNew($form)) {
			$this->layout->data->title = Site::_('Create a New Account');
		} else {
			$this->layout->data->title = Site::_('Edit Account Details');
		}
	}

	// }}}
	// {{{ protected function setWidgetValues()

	protected function setWidgetValues(SiteAccount $account)
	{
		$this->ui->getWidget('fullname')->value = $account->fullname;
		$this->ui->getWidget('email')->value = $account->email;
		$this->ui->getWidget('confirm_email')->value = $account->email;
	}

	// }}}
	// {{{ protected function load()

	protected function load(SwatForm $form)
	{
		$this->assignObjectValuesToUi($this->account, array(
			'fullname',
			'email',
		));

		$this->ui->getWidget('confirm_email')->value = $this->account->email;
	}

	// }}}
}

?>
