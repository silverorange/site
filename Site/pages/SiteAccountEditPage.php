<?php

require_once 'Site/dataobjects/SiteAccount.php';
require_once 'Site/pages/SiteAccountPage.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatUI.php';

/**
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountEditPage extends SiteAccountPage
{
	// {{{ protected properties

	protected $ui;
	protected $ui_xml = 'Site/pages/account-edit.xml';

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->ui_xml);

		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;

		$confirm_password = $this->ui->getWidget('confirm_password');
		$confirm_password->password_widget = $this->ui->getWidget('password');;

		$confirm_email = $this->ui->getWidget('confirm_email');
		$confirm_email->email_widget = $this->ui->getWidget('email');;

		$this->ui->init();
	}

	// }}}
	// {{{ private function findAccount()

	private function findAccount()
	{
		if ($this->app->session->isLoggedIn())
			return $this->app->session->account;

		return $this->createNewAccount();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		if ($this->app->session->isLoggedIn()) {
			$this->ui->getWidget('password')->required = false;
			$this->ui->getWidget('confirm_password')->required = false;
		}

		$form = $this->ui->getWidget('edit_form');
		$form->process();

		if ($form->isProcessed()) {
			$this->validateEmail();

			if (!$form->hasMessage()) {
				$account = $this->findAccount();

				$this->updateAccount($account);

				if (!$this->app->session->isLoggedIn()) {

					$account->createdate = new SwatDate();
					$account->createdate->toUTC();

					$account->setDatabase($this->app->db);
					$account->save();

					$this->app->session->loginById($account->id);

					$message = new SwatMessage(
						Site::_('New account has been created.'));

				} elseif ($this->app->session->account->isModified()) {
					$message = new SwatMessage(
						Site::_('Account details have been updated.'));

					$this->app->messages->add($message);

					$this->app->session->account->save();
				}

				$this->app->relocate('account');
			}
		}
	}

	// }}}
	// {{{ protected function updateAccount()

	protected function updateAccount(StoreAccount $account)
	{
		if (!$this->app->session->isLoggedIn()) {
			$account->setPassword(
				$this->ui->getWidget('password')->value);
		}

		$account->fullname = $this->ui->getWidget('fullname')->value;
		$account->email = $this->ui->getWidget('email')->value;
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
		$class_name = SwatDBClassMap::get('StoreAccount');
		$account = new $class_name();

		if ($this->app->hasModule('SiteMultipleInstanceModule'))
			$account->instance = $this->app->instance->getInstance();

		return $account;
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

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->buildInternal();

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;

		if ($this->app->session->isLoggedIn()) {
			$this->layout->navbar->createEntry(
				Site::_('Edit Account Details'));

			$this->layout->data->title = Site::_('Edit Account Details');
			$this->ui->getWidget('submit_button')->title =
				Site::_('Update Account Details');

			$this->ui->getWidget('password_container')->visible = false;
		} else {
			$this->layout->navbar->createEntry(
				Site::_('Create a New Account'));

			$this->layout->data->title = Site::_('Create a New Account');
		}

		if ($this->app->session->isLoggedIn() && !$form->isProcessed()) {
			$account = $this->findAccount();
			$this->setWidgetValues($account);
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

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
