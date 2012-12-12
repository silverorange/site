<?php

require_once 'Site/pages/SiteEditPage.php';

/**
 * Page for changing the password of an account
 *
 * @package   Site
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAccount
 */
class SiteAccountChangePasswordPage extends SiteEditPage
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/pages/account-change-password.xml';
	}

	// }}}
	// {{{ protected function isNew()

	protected function isNew(SwatForm $form)
	{
		return false;
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		if (!$this->app->session->isLoggedIn())
			$this->app->relocate('account/login');

		parent::init();
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$confirm = $this->ui->getWidget('confirm_password');
		$confirm->password_widget = $this->ui->getWidget('password');
	}

	// }}}

	// process phase
	// {{{ protected function save()

	protected function save(SwatForm $form)
	{
		$this->updatePassword();

		$this->app->session->account->save();

		$message = new SwatMessage(Site::_(
			'Account password has been updated.'));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function validate()

	protected function validate(SwatForm $form)
	{
		$account = $this->app->session->account;
		$old_password = $this->ui->getWidget('old_password');

		if (!$old_password->hasMessage() &&
			!$account->isCorrectPassword($old_password->value)) {
			$message = new SwatMessage(
				Site::_('Your password is incorrect.'),
				'error'
			);

			$message->content_type = 'text/xml';
			$old_password->addMessage($message);
		}
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
	}

	// }}}

	// build phase
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		parent::buildTitle();
		$this->layout->data->title = Site::_('Choose a New Password');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		if (!property_exists($this->layout, 'navbar'))
			return;

		$this->layout->navbar->createEntry(Site::_('New Password'));
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
		$this->layout->addBodyClass('account-change-password');
	}

	// }}}
}

?>
