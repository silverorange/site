<?php

require_once 'Text/Password.php';
require_once 'Swat/SwatUI.php';
require_once 'Site/dataobjects/SiteAccount.php';
require_once 'Site/pages/SiteUiPage.php';
require_once 'Site/dataobjects/SiteAccount.php';

/**
 * Page for requesting a new password for forgotten account passwords
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAccount
 * @see       SiteAccountResetPasswordPage
 */
class SiteAccountForgotPasswordPage extends SiteUiPage
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/pages/account-forgot-password.xml';
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('password_form');

		$form->process();

		if ($form->isProcessed()) {
			if (!$form->hasMessage())
				$this->generatePassword();

			if (!$form->hasMessage())
				$this->app->relocate('account/forgotpassword/sent');
		}
	}

	// }}}
	// {{{ protected function getAccount()

	/**
	 * Gets the account to which to sent the forgot password email
	 *
	 * @param string $email the email address of the account.
	 *
	 * @return SiteAccount the account or null if no such account exists.
	 */
	protected function getAccount($email)
	{
		$instance = ($this->app->hasModule('SiteMultipleInstanceModule')) ?
			$this->app->instance->getInstance() : null;

		$class_name = SwatDBClassMap::get('SiteAccount');
		$account = new $class_name();
		$account->setDatabase($this->app->db);
		$found = $account->loadWithEmail($email, $instance);

		if ($found === false)
			$account = null;

		return $account;
	}

	// }}}
	// {{{ private function generatePassword()

	private function generatePassword()
	{
		$email = $this->ui->getWidget('email')->value;

		$account = $this->getAccount($email);

		if ($account === null) {
			$message = new SwatMessage(Site::_(
				'There is no account with this email address.'),
				SwatMessage::ERROR);

			$message->secondary_content = sprintf(Site::_(
				'Make sure you entered it correctly, or '.
				'%screate a New Account%s.'),
				'<a href="account/edit">', '</a>');

			$message->content_type = 'text/xml';
			$this->ui->getWidget('email')->addMessage($message);
		} else {
			$password_tag = $account->resetPassword($this->app);
			$password_link = $this->app->getBaseHref().
				'account/resetpassword/'.$password_tag;

			$account->sendResetPasswordMailMessage($this->app, $password_link);
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('password_form')->action = $this->source;

		$email = $this->app->initVar('email');
		if ($email !== null)
			$this->ui->getWidget('email')->value = $email;
	}

	// }}}
}

?>
