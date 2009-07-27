<?php

require_once 'Site/pages/SiteEditPage.php';
require_once 'Swat/SwatUI.php';

/**
 * Page to reset the password for an account
 *
 * Users are required to enter a new password.
 *
 * @package   Site
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAccount
 * @see       SiteAccountForgotPasswordPage
 */
class SiteAccountResetPasswordPage extends SiteEditPage
{
	// {{{ private properties

	private $account_id;

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
			if ($this->app->session->isLoggedIn())
				$this->app->relocate('account/changepassword');
			else
				$this->app->relocate('account/forgotpassword');
		}

		$this->account_id = $this->getAccountId($tag);

		$confirm = $this->ui->getWidget('confirm_password');
		$confirm->password_widget = $this->ui->getWidget('password');;
	}

	// }}}
	// {{{ protected function getAccountId()

	/**
	 * Gets the account id of the account associated with the password tag
	 *
	 * @param string $password_tag the password tag.
	 *
	 * @return integer the account id of the account associated with the
	 *                  password tag or null if no such account id exists.
	 */
	protected function getAccountId($password_tag)
	{
		$sql = sprintf('select id from Account where password_tag = %s',
			$this->app->db->quote($password_tag, 'text'));

		if ($this->app->hasModule('SiteMultipleInstanceModule')) {
			$instance_id = $this->app->getInstanceId();
			$sql.= sprintf(' and instance %s %s',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));
		}

		return SwatDB::queryOne($this->app->db, $sql);
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		if ($this->account_id === null)
			return;

		parent::process();
	}

	// }}}
	// {{{ protected function save()

	protected function save(SwatForm $form)
	{
		$this->app->session->loginById($this->account_id);

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

	protected function updatePassword(SwatForm $form)
	{
		$password = $this->ui->getWidget('password')->value;
		$this->app->session->account->setPassword($password);
		$this->app->session->account->password_tag = null;
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		if ($this->account_id === null) {
			$text = sprintf('<p>%s</p><ul><li>%s</li><li>%s</li></ul>',
				Site::_('Please verify that the link is exactly the same as '.
					'the one emailed to you.'),
				Site::_('If you requested an email more than once, only the '.
					'most recent link will work.'),
				sprintf(Site::_('If you have lost the link sent in the '.
					'email, you may %shave the email sent again%s.'),
					'<a href="account/forgotpassword">', '</a>'));

			$message = new SwatMessage(Site::_('Link Incorrect'),
				SwatMessage::WARNING);

			$message->secondary_content = $text;
			$message->content_type = 'text/xml';
			$this->ui->getWidget('message_display')->add($message);

			$this->ui->getWidget('field_container')->visible = false;

			$this->layout->clear('content');
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
		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/site/styles/site-account-reset-password-page.css',
			Site::PACKAGE_ID));
	}

	// }}}
}

?>
