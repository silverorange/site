<?php

require_once 'Site/pages/SiteEditPage.php';
require_once 'Swat/SwatUI.php';

/**
 * Page to reset the password for an account
 *
 * Users are required to enter a new password.
 *
 * @package   Site
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAccount
 * @see       SiteAccountForgotPasswordPage
 */
class SiteAccountResetPasswordPage extends SiteEditPage
{
	// {{{ protected properties

	protected $account;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/pages/account-reset-password.xml';
	}

	// }}}
	// {{{ protected function isNew()

	protected function isNew(SwatForm $form)
	{
		return false;
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'tag' => array(0, null),
		);
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$tag = $this->getArgument('tag');

		if ($tag === null) {
			if ($this->app->session->isLoggedIn()) {
				$this->app->relocate($this->getChangePasswordSource());
			} else {
				$this->app->relocate($this->getForgotPasswordSource());
			}
		}

		$this->account = $this->getAccount($tag);

		$confirm = $this->ui->getWidget('confirm_password');
		$confirm->password_widget = $this->ui->getWidget('password');
	}

	// }}}
	// {{{ protected function getAccount()

	/**
	 * Gets the account id of the account associated with the password tag
	 *
	 * @param string $password_tag the password tag.
	 *
	 * @return SiteAccount the account associated with the password tag or
	 *                      null if no such account id exists.
	 */
	protected function getAccount($password_tag)
	{
		$class = SwatDBClassMap::get('SiteAccount');
		$account = new $class();
		$account->setDatabase($this->app->db);

		return $account->loadByPasswordTag($password_tag);
	}

	// }}}
	// {{{ protected function getChangePasswordSource()

	protected function getChangePasswordSource()
	{
		return 'account/changepassword';
	}

	// }}}
	// {{{ protected function getForgotPasswordSource()

	protected function getForgotPasswordSource()
	{
		return 'account/forgotpassword';
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		if (!$this->account instanceof SiteAccount)
			return;

		parent::process();
	}

	// }}}
	// {{{ protected function save()

	protected function save(SwatForm $form)
	{
		$this->app->session->loginByAccount($this->account);

		$this->updatePassword();

		$this->app->session->account->save();

		$message = new SwatMessage(Site::_(
			'Account password has been updated.'));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate(SwatForm $form)
	{
		$this->app->relocate('account');
	}

	// }}}
	// {{{ protected function updatePassword()

	protected function updatePassword()
	{
		$account  = $this->app->session->account;

		$password = $this->ui->getWidget('password')->value;

		$account->setPassword($password);
		$account->password_tag = null;
	}

	// }}}

	// build phase
	// {{{ protected function buildForm()

	protected function buildForm(SwatForm $form)
	{
		parent::buildForm($form);

		if (!$this->account instanceof SiteAccount) {
			$text = sprintf(
				'<p>%s</p><ul><li>%s</li><li>%s</li></ul>',
				Site::_(
					'Please verify that the link is exactly the same as '.
					'the one emailed to you.'
				),
				Site::_(
					'If you requested an email more than once, only the '.
					'most recent link will work.'
				),
				sprintf(
					Site::_(
						'If you have lost the link sent in the '.
						'email, you may %shave the email sent again%s.'
					),
					sprintf(
						'<a href="%s">',
						$this->getForgotPasswordSource()
					),
					'</a>'
				)
			);

			$message = new SwatMessage(Site::_('Link Incorrect'), 'warning');
			$message->secondary_content = $text;
			$message->content_type = 'text/xml';

			$message_display = $this->getMessageDisplay($form);
			$message_display->add($message, SwatMessageDisplay::DISMISS_OFF);

			$this->ui->getWidget('field_container')->visible = false;

			if (isset($this->layout->data->content)) {
				$this->layout->clear('content');
			}
		}
	}

	// }}}
	// {{{ protected function load()

	protected function load(SwatForm $form)
	{
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addBodyClass('account-reset-password-page');

		$this->layout->addHtmlHeadEntry(
			'packages/site/styles/site-account-reset-password-page.css',
			Site::PACKAGE_ID
		);
	}

	// }}}
}

?>
