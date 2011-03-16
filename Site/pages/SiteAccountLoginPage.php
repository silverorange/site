<?php

require_once 'Swat/SwatUI.php';
require_once 'Site/pages/SitePage.php';

/**
 * Page for logging into an account
 *
 * @package   Site
 * @copyright 2006-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAccount
 */
class SiteAccountLoginPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/pages/account-login.xml';

	protected $ui;

	/**
	 * @var string
	 */
	protected $relocate_uri = 'account';

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->ui_xml);

		$login_form = $this->ui->getWidget('login_form');
		$login_form->action = $this->source;

		$create_form = $this->ui->getWidget('create_account_form');
		$create_form->action = 'account/edit';

		$this->initRelocateUri($login_form);
		$this->loggedInRelocate();

		$this->ui->init();
	}

	// }}}
	// {{{ protected function initRelocateUri()

	protected function initRelocateUri(SwatForm $form)
	{
		$relocate_uri = null;

		$get_uri = SiteApplication::initVar('relocate', null,
			SiteApplication::VAR_GET);

		// only use relative URIs
		if (!preg_match('#^(?:[a-zA-Z]+:)?//#', $get_uri))
			$relocate_uri = $get_uri;

		if ($relocate_uri === null)
			$relocate_uri = $form->getHiddenField('relocate_uri');

		if ($relocate_uri !== null)
			$this->relocate_uri = $relocate_uri;

		$form->addHiddenField('relocate_uri', $this->relocate_uri);
	}

	// }}}
	// {{{ protected function loggedInRelocate()

	protected function loggedInRelocate()
	{
		// go to details page if already logged in
		if ($this->app->session->isLoggedIn())
			$this->app->relocate($this->relocate_uri);
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('login_form');

		$form->process();

		if ($form->isProcessed() && !$form->hasMessage()) {
			$email = $this->ui->getWidget('email_address')->value;
			$password = $this->ui->getWidget('password')->value;

			if ($this->app->session->login($email, $password)) {
				$this->postLoginProcess();
				$this->app->relocate($this->relocate_uri);
			} else {
				$message = new SwatMessage(Site::_('Login Incorrect'),
					SwatMessage::WARNING);

				$message->secondary_content = sprintf(
					'<ul><li>%s</li><li>%s</li></ul>',
					Site::_('Please check the spelling on your email '.
						'address or password.'),
					sprintf(Site::_('Password is case-sensitive. Make sure '.
						'your %sCaps Lock%s key is off.'),
						'<kbd>', '</kbd>'));

				$message->content_type = 'text/xml';
				$this->ui->getWidget('message_display')->add($message);
			}
		}
	}

	// }}}
	// {{{ protected function postLoginProcess()

	protected function postLoginProcess()
	{
		// TODO: why is there a 'cart' reference in Site?
		$this->app->cart->save();
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->buildForgotPasswordLink();

		$this->layout->startCapture('content', true);
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildForgotPasswordLink()

	protected function buildForgotPasswordLink()
	{
		$this->ui->getWidget('forgot_password')->content =
			$this->getForgotPasswordLink();
	}

	// }}}
	// {{{ protected function getForgotPasswordLink()

	protected function getForgotPasswordLink()
	{
		$email = $this->ui->getWidget('email_address');
		$link = sprintf(Site::_(' %sForgot your password?%s'),
			'<a href="account/forgotpassword%s">', '</a>');

		if (!$email->hasMessage() && $email != null) {
			$link_value = sprintf('?email=%s', urlencode($email->value));
		} else {
			$link_value = null;
		}

		return sprintf($link, $link_value);
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
			'packages/site/styles/site-account-login-page.css',
			Site::PACKAGE_ID));
	}

	// }}}
}

?>
