<?php

require_once 'Site/pages/SiteArticlePage.php';
require_once 'Swat/SwatUI.php';

/**
 * Page to reset the password for an account
 *
 * Users are required to enter a new password.
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAccount
 * @see       SiteAccountForgotPasswordPage
 */
class SiteAccountResetPasswordPage extends SiteArticlePage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/pages/account-reset-password.xml';

	protected $ui;

	// }}}
	// {{{ private properties

	private $account_id;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteAbstractPage $page)
	{
		parent::__construct($page);

		if ($this->getArgument('tag') === null) {
			if ($this->app->session->isLoggedIn())
				$this->app->relocate('account/changepassword');
			else
				$this->app->relocate('account/forgotpassword');
		}
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
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->ui_xml);

		$this->initInternal();
		$this->ui->init();
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;

		$confirm = $this->ui->getWidget('confirm_password');
		$confirm->password_widget = $this->ui->getWidget('password');;

		$this->account_id = $this->getAccountId($this->getArgument('tag'));
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
			$instance_id = $this->app->instance->getId();
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
		parent::process();

		if ($this->account_id === null)
			return;

		$form = $this->ui->getWidget('edit_form');
		$form->process();

		if ($form->isProcessed()) {
			if (!$form->hasMessage()) {
				$this->app->session->loginById($this->account_id);

				$password = $this->ui->getWidget('password')->value;
				$this->app->session->account->setPassword($password);
				$this->app->session->account->password_tag = null;
				$this->app->session->account->save();

				$this->app->messages->add(new SwatMessage(
						Site::_('Account password has been updated.')));

				$this->relocate();
			}
		}
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$this->app->relocate('account');
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;

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

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/site/styles/site-account-reset-password-page.css',
			Site::PACKAGE_ID));
	}

	// }}}
}

?>
