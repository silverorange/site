<?php

require_once 'Site/dataobjects/SiteAccount.php';
require_once 'Site/pages/SiteDBEditPage.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatUI.php';

/**
 * @package   Site
 * @copyright 2006-2010 silverorange
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

		if ($this->ui->hasWidget('confirm_password')) {
			$confirm_password = $this->ui->getWidget('confirm_password');
			$confirm_password->password_widget =
				$this->ui->getWidget('password');
		}

		if ($this->ui->hasWidget('confirm_email')) {
			$confirm_email = $this->ui->getWidget('confirm_email');
			$confirm_email->email_widget = $this->ui->getWidget('email');
		}
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
		if ($form->id == 'edit_form')
			$this->validateEmail($form);
	}

	// }}}
	// {{{ protected function validateEmail()

	protected function validateEmail(SwatForm $form)
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

		if ($found && $this->account->id !== $account->id) {
			$message = $this->getInvalidEmailMessage();
			if ($message instanceof SwatMessage) {
				$email->addMessage($message);
			}
		}
	}

	// }}}
	// {{{ protected function getInvalidEmailMessage()

	protected function getInvalidEmailMessage()
	{
		$message = new SwatMessage(
			Site::_('An account already exists with this email address.'),
			'error');

		if ($this->isNew($this->ui->getWidget('edit_form'))) {
			$email_link = sprintf('<a href="account/forgotpassword?email=%s">',
				$this->ui->getWidget('email')->value);

			$message->secondary_content =
				sprintf(Site::_('You can %srequest a new password%s to '.
					'log into the existing account.'), $email_link, '</a>');

			$message->content_type = 'text/xml';
		}

		return $message;
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
		if ($form->id == 'edit_form') {
			$this->updateAccount($form);
			// getSavedMessage() needs to be called here so that the isNew()
			// calls it depends on are still accurate.
			$message = $this->getSavedMessage($form);

			if ($this->isNew($form)) {
				$password = $this->ui->getWidget('password')->value;
				if ($password != null)
					$this->account->setPassword($password);

				$this->account->createdate = new SwatDate();
				$this->account->createdate->toUTC();

				$this->account->setDatabase($this->app->db);
				$this->account->save();
				$this->loginAccount();
			} elseif ($this->account->isModified()) {
				$this->account->save();

				if ($this->app->session->account->id === $this->account->id) {
					$this->app->session->account = $this->account;
				}
			}

			if ($message instanceof SwatMessage)
				$this->app->messages->add($message);
		}
	}

	// }}}
	// {{{ protected function loginAccount()

	protected function loginAccount()
	{
		$this->app->session->loginById($this->account->id);
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate(SwatForm $form)
	{
		$this->app->relocate('account');
	}

	// }}}
	// {{{ protected function getSavedMessage()

	protected function getSavedMessage(SwatForm $form)
	{
		$message = '';

		if ($this->isNew($form)) {
			$message = Site::_('Your account has been created.');
		} else {
			$message = Site::_('Account details have been updated.');
		}

		return new SwatMessage($message);
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
	// {{{ protected function load()

	protected function load(SwatForm $form)
	{
		$this->assignObjectValuesToUi($this->account, array(
			'fullname',
			'email',
		));

		if ($this->ui->hasWidget('confirm_email')) {
			$this->ui->getWidget('confirm_email')->value =
				$this->account->email;
		}
	}

	// }}}
}

?>
