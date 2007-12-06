<?php

require_once 'Site/pages/SiteAccountPage.php';
require_once 'Swat/SwatUI.php';

/**
 * Page for changing the password of an account
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAccount
 */
class SiteAccountChangePasswordPage extends SiteAccountPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/pages/account-change-password.xml';
	protected $ui;

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

		$confirm = $this->ui->getWidget('confirm_password');
		$confirm->password_widget = $this->ui->getWidget('password');

		$this->ui->init();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('edit_form');
		$form->process();

		if ($form->isProcessed()) {
			if (!$form->hasMessage())
				$this->validate();

			if (!$form->hasMessage()) {
				$password = $this->ui->getWidget('password')->value;
				$this->app->session->account->setPassword($password);
				$this->app->session->account->save();

				$message = new SwatMessage(Site::_(
					'Account password has been updated.'));

				$this->app->messages->add($message);

				$this->app->relocate('account');
			}
		}
	}

	// }}}
	// {{{ private function validate()

	private function validate()
	{
		$account = $this->app->session->account;

		$old_password = $this->ui->getWidget('old_password');

		$salt = $account->password_salt;
		// salt might be base-64 encoded
		$decoded_salt = base64_decode($salt, true);
		if ($decoded_salt !== false)
			$salt = $decoded_salt;

		$value = md5($old_password->value.$salt);

		if ($value != $account->password) {
			$message = new SwatMessage(Site::_('Your password is incorrect.'),
				SwatMessage::ERROR);

			$message->content_type = 'text/xml';
			$old_password->addMessage($message);
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->navbar->createEntry(Site::_('New Password'));
		$this->layout->data->title = Site::_('Choose a New Password');

		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
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
