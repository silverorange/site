<?php

require_once 'Site/pages/SiteUiPage.php';

/**
 * Page for logging into an account
 *
 * @package   Site
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAccount
 */
class SiteAccountLoginPage extends SiteUiPage
{
	// {{{ protected properties

	/**
	 * @var string
	 *
	 * @see SiteAccountLoginPage::initRelocateURI();
	 */
	protected $relocate_uri = null;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/pages/account-login.xml';
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initRelocateURI($this->ui->getWidget('login_form'));
		$this->loggedInRelocate();
	}

	// }}}
	// {{{ protected function initRelocateURI()

	protected function initRelocateURI(SwatForm $form)
	{
		$relocate_uri = null;

		$get_uri = SiteApplication::initVar(
			'relocate',
			null,
			SiteApplication::VAR_GET
		);

		// only use relative URIs
		if ($get_uri != '' && !preg_match('#^(?:[a-zA-Z]+:)?//#', $get_uri)) {
			$relocate_uri = $get_uri;
		}

		if ($relocate_uri === null) {
			$relocate_uri = $form->getHiddenField('relocate_uri');
		}

		if ($relocate_uri === null) {
			$this->relocate_uri = $this->getDefaultRelocateURI();
		} else {
			$this->relocate_uri = $relocate_uri;
		}

		$form->addHiddenField('relocate_uri', $this->relocate_uri);
	}

	// }}}
	// {{{ protected function loggedInRelocate()

	protected function loggedInRelocate()
	{
		if ($this->app->session->isLoggedIn()) {
			$this->app->relocate($this->relocate_uri);
		}
	}

	// }}}
	// {{{ protected function getDefaultRelocateURI()

	protected function getDefaultRelocateURI()
	{
		return 'account';
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$form = $this->ui->getWidget('login_form');

		if ($form->isProcessed() && !$form->hasMessage()) {
			$email = $this->ui->getWidget('email_address')->value;
			$password = $this->ui->getWidget('password')->value;

			if ($this->app->session->login($email, $password)) {
				$this->postLoginProcess();
				$this->app->relocate($this->relocate_uri);
			} else {
				$message = new SwatMessage(
					Site::_('Login Incorrect'),
					'warning'
				);

				$message->secondary_content = sprintf(
					'<ul><li>%s</li><li>%s</li></ul>',
					Site::_(
						'Please check the spelling on your email '.
						'address or password.'
					),
					sprintf(
						Site::_(
							'Password is case-sensitive. Make sure '.
							'your %sCaps Lock%s key is off.'
						),
						'<kbd>',
						'</kbd>'
					)
				);

				$message->content_type = 'text/xml';
				$this->ui->getWidget('message_display')->add($message);
			}
		}
	}

	// }}}
	// {{{ protected function postLoginProcess()

	protected function postLoginProcess()
	{
		// save persistent login if stay-logged-in is checked
		if ($this->app->config->account->persistent_login_enabled &&
			$this->ui->getWidget('stay_logged_in')->value) {
			$this->app->session->setLoginCookie();
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$login_form = $this->ui->getWidget('login_form');
		$login_form->action = $this->source;

		if ($this->app->config->account->persistent_login_enabled) {
			$this->ui->getWidget('stay_logged_in_field')->visible = true;
		}

		$this->buildForgotPasswordLink();
		$this->buildNewCustomersFrame();
	}

	// }}}
	// {{{ protected function buildNewCustomersFrame()

	protected function buildNewCustomersFrame()
	{
		$create_form = $this->ui->getWidget('create_account_form');
		$create_form->action = 'account/edit';
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
		$href = 'account/forgotpassword';

		$email = $this->ui->getWidget('email_address');
		if (!$email->hasMessage() && trim($email->value) != '') {
			$href.= '?email='.urlencode($email->value);
		}

		$link = new SwatHtmlTag('a');
		$link->setContent(Site::_('Forgot your password?'));
		$link->href = $href;
		$link->tabindex = 4;

		return $link;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addBodyClass('account-login-page');

		$this->layout->addHtmlHeadEntry(
			'packages/site/styles/site-account-login-page.css',
			Site::PACKAGE_ID
		);
	}

	// }}}
}

?>
