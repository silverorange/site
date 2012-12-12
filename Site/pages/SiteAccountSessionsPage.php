<?php

require_once 'Site/pages/SiteDBEditPage.php';

/**
 * Page for listing current persistent login sessions
 *
 * @package   Site
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAccount
 * @see       SiteAccountLoginSession
 */
class SiteAccountSessionsPage extends SiteDBEditPage
{
	// {{{ protected properties

	/**
	 * @var array
	 */
	protected $logout_buttons = array();

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/pages/account-sessions.xml';
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		// redirect to login page if not logged in
		if (!$this->app->session->isLoggedIn()) {
			$uri = sprintf('account/login?relocate=%s', $this->source);
			$this->app->relocate($uri);
		}

		parent::init();
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->initLogoutButtons();
	}

	// }}}
	// {{{ protected function initLogoutButtons()

	protected function initLogoutButtons()
	{
		$form = $this->ui->getWidget('sessions_form');

		foreach ($this->getOtherLoginSessions() as $session) {
			$button = $this->createLogoutButton($session);
			$this->logout_buttons[$session->id] = $button;
			$form->add($button);
		}
	}

	// }}}
	// {{{ protected function createLogoutButton()

	protected function createLogoutButton(SiteAccountLoginSession $session)
	{
		$button = new SwatButton();
		$button->id = 'logout_button_'.$session->id;
		$button->title = Site::_('End Session');
		$button->visible = false;
		return $button;
	}

	// }}}

	// process phase
	// {{{ protected function saveData()

	protected function saveData(SwatForm $form)
	{
		foreach ($this->logout_buttons as $id => $button) {
			if ($button->hasBeenClicked()) {
				$this->deleteLoginSession($id);
				break;
			}
		}
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate(SwatForm $form)
	{
		$this->relocateToRefererUri($form, 'account');
	}

	// }}}
	// {{{ protected function deleteLoginSession()

	protected function deleteLoginSession($id)
	{
		$session = (isset($this->app->session->account->login_sessions[$id]))
			? $this->app->session->account->login_sessions[$id]
			: null;

		if ($session instanceof SiteAccountLoginSession) {
			$this->endSession($session);
			$session->delete();
			$message = $this->getLogoutMessage($session);
		} else {
			$message = new SwatMessage(Site::_('Session has been ended.'));
		}

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function endSession()

	protected function endSession(SiteAccountLoginSession $session)
	{
		// extra sanity check so you are only ending your own sessions
		if ($session->getInternalValue('account') !==
			$this->app->session->account->id) {
			return;
		}

		$current_session_id = $this->app->session->getSessionId();
		$login_session_id   = $session->session_id;

		// end current session
		session_write_close();

		// start login session
		session_id($login_session_id);
		session_start();

		// clear all session data
		$_SESSION = array();

		// destroy session file
		session_destroy();

		// resume current session
		session_id($current_session_id);
		session_start();
	}

	// }}}
	// {{{ protected function getLogoutMessage()

	protected function getLogoutMessage(SiteAccountLoginSession $login_session)
	{
		$message = new SwatMessage(
			sprintf(
				Site::_('%s on %s session has been ended.'),
				SwatString::minimizeEntities(
					$this->getBrowser($login_session->user_agent)
				),
				SwatString::minimizeEntities(
					$this->getOS($login_session->user_agent)
				)
			)
		);

		$view_link = new SwatHtmlTag('a');
		$view_link->href = 'account/sessions';
		$view_link->setContent(Site::_('remaining active sessions'));

		$message->secondary_content = sprintf(Site::_('View %s.'), $view_link);
		$message->content_type = 'text/xml';

		return $message;
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$other_login_sessions = $this->getOtherLoginSessions();

		ob_start();

		$this->displaySessionsNote();

		echo '<div class="account-sessions-current">';

		$header = new SwatHtmlTag('h4');
		$header->setContent(Site::_('Current Session'));
		$header->display();

		echo '<ul class="account-sessions-current-list">';

		$this->displayCurrentSession();

		echo '</ul>';
		echo '</div>';

		if (count($other_login_sessions) > 0) {
			echo '<div class="account-sessions-other">';

			$header = new SwatHtmlTag('h4');
			$header->setContent(Site::_('On Other Devices'));
			$header->display();

			$this->displayOtherSessionsNote();

			echo '<ul class="account-sessions-other-list">';

			foreach ($other_login_sessions as $session) {
				// For now, just check to make sure the session exists before
				// displaying the details. This should be moved into a cleanup
				// cron.
				if ($this->app->session->sessionFileExists(
					$session->session_id)) {
					$this->displayLoginSession($session);
				}
			}

			echo '</ul>';
			echo '</div>';
		}

		$this->ui->getWidget('sessions_content')->content = ob_get_clean();
		$this->ui->getWidget('sessions_content')->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function getOtherLoginSessions()

	protected function getOtherLoginSessions()
	{
		$sessions = array();

		$current_login_tag = $this->app->session->getCurrentLoginTag();

		foreach ($this->app->session->account->login_sessions as $session) {
			if ($session->tag !== $current_login_tag) {
				$sessions[] = $session;
			}
		}

		return $sessions;
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		parent::buildTitle();
		$this->layout->data->title = Site::_('Active Sessions');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		if (!property_exists($this->layout, 'navbar')) {
			return;
		}

		$this->layout->navbar->createEntry(Site::_('Active Sessions'));
	}

	// }}}
	// {{{ protected function getBrowser()

	protected function getBrowser($user_agent)
	{
		$browsers = array(
			'blackberry'    => 'Blackberry Browser',
			'chromium'      => 'Chromium',
			'chrome'        => 'Chrome',
			'android'       => 'Android Browser',
			'mobile-safari' => 'Mobile Safari',
			'safari'        => 'Safari',
			'maemo'         => 'Maemo Browser',
			'iceweasel'     => 'Iceweasel',
			'firefox'       => 'Firefox',
			'opera'         => 'Opera',
			'msie'          => 'Internet Explorer',
			's60'           => 'Series60 Browser',
			'mozilla'       => 'Mozilla Compatible',
			'other'         => 'Unknown Browser',
		);

		$exps = array(
			'blackberry'    => '#BlackBerry #',
			'chromium'      => '#Chromium/#',
			'chrome'        => '#Chrome/|CriOS/#',
			'android'       => '#Android #',
			'mobile-safari' => '#Mobile/[A-Z0-9]+ Safari/#',
			'safari'        => '#Safari/#',
			'maemo'         => '#Maemo Browser #',
			'iceweasel'     => '#Iceweasel/#',
			'firefox'       => '#Firefox/#',
			'opera'         => '#Opera/#',
			'msie'          => '#MSIE #',
			's60'           => '#Series60/#',
			'mozilla'       => '#Mozilla/#',
		);

		$found_browser = $browsers['other'];

		foreach ($exps as $browser => $exp) {
			if (preg_match($exp, $user_agent) === 1) {
				$found_browser = $browsers[$browser];
				break;
			}
		}

		return $found_browser;
	}

	// }}}
	// {{{ protected function getOS()

	protected function getOS($user_agent)
	{
		$oses = array(
			'windows-8'     => 'Windows 8',
			'windows-7'     => 'Windows 7',
			'windows-vista' => 'Windows Vista',
			'windows-xp'    => 'Windows XP',
			'windows-phone' => 'Windows Phone',
			'windows'       => 'Windows',
			'blackberry'    => 'Blackberry OS',
			'android'       => 'Android',
			'ios-ipad'      => 'iPad',
			'ios-ipod'      => 'iPod Touch',
			'ios-iphone'    => 'iPhone',
			'mac-osx'       => 'Mac OS X',
			'linux-ubuntu'  => 'Ubuntu Linux',
			'linux-fedora'  => 'Fedora Linux',
			'linux-maemo'   => 'Maemo',
			'linux'         => 'Linux',
			'symbian'       => 'Symbian',
			'other'         => 'Unknown Operating System',
		);

		$exps = array(
			'windows-8'     => '#Windows NT 6.2#',
			'windows-7'     => '#Windows NT 6.1#',
			'windows-vista' => '#Windows NT 6.0#',
			'windows-xp'    => '#Windows NT 5.1|Windows NT 5.2; (?:WOW64|Win64);#',
			'windows-phone' => '#Windows (?:Phone|Mobile|CE)#',
			'windows'       => '#Windows #',
			'blackberry'    => '#BlackBerry#',
			'android'       => '#Android [1-9]#',
			'ios-ipad'      => '#iPad;#',
			'ios-iphone'    => '#iPhone;#',
			'ios-ipod'      => '#iPod;#',
			'mac-osx'       => '#Mac OS X#',
			'linux-ubuntu'  => '#Ubuntu\/[1-9]#',
			'linux-fedora'  => '#Fedora\/[1-9]#',
			'linux-maemo'   => '#Maemo;|Linux armv7l;#',
			'linux'         => '#Linux #',
			'symbian'       => '#S60;|SymbOS;|SymbianOS/[1-9]\.[0-9];#',
		);

		$found_os = $oses['other'];

		foreach ($exps as $os => $exp) {
			if (preg_match($exp, $user_agent) === 1) {
				$found_os = $oses[$os];
				break;
			}
		}

		return $found_os;
	}

	// }}}
	// {{{ protected function displaySessionsNote()

	protected function displaySessionsNote()
	{
		$div = new SwatHtmlTag('div');
		$div->class = 'account-sessions-note';

		$div->setContent(
			Site::_(
				'If you log in from multiple devices (for example, your '.
				'phone and your desktop) you can view and manage your '.
				'login sessions on this page.'
			)
		);

		$div->display();
	}

	// }}}
	// {{{ protected function displayOtherSessionsNote()

	protected function displayOtherSessionsNote()
	{
		$div = new SwatHtmlTag('div');
		$div->class = 'account-sessions-note';

		$div->setContent(
			Site::_(
				'If you notice any unfamiliar '.
				'devices or you forgot to log out from any device you can end '.
				'those sessions below, removing access to your account from '.
				'those devices until the next time you use them.'
			)
		);

		$div->display();
	}

	// }}}
	// {{{ protected function displayCurrentSession()

	protected function displayCurrentSession()
	{
		$current_login     = null;
		$current_login_tag = $this->app->session->getCurrentLoginTag();

		if ($current_login_tag !== null) {
			foreach ($this->app->session->account->login_sessions as $session) {
				if ($session->tag === $current_login_tag) {
					$current_login = $session;
					break;
				}
			}
		}

		if ($current_login instanceof SiteAccountLoginSession) {

			$user_agent = $current_login->user_agent;
			$login_date = $current_login->login_date;

		} else {

			$user_agent = null;

			if (isset($_SERVER['HTTP_USER_AGENT'])) {

				$user_agent = $_SERVER['HTTP_USER_AGENT'];

				// Filter bad character encoding. If invalid, assume ISO-8859-1
				// encoding and convert to UTF-8.
				if (!SwatString::validateUtf8($user_agent)) {
					$user_agent = iconv('ISO-8859-1', 'UTF-8', $user_agent);
				}

			}

			$login_date = $this->app->session->account->last_login;

		}


		$this->displayLoginInformation($user_agent, $login_date);
	}

	// }}}
	// {{{ protected function displayLoginSession()

	protected function displayLoginSession(SiteAccountLoginSession $session)
	{
		$button = (isset($this->logout_buttons[$session->id]))
			? $this->logout_buttons[$session->id]
			: null;

		$this->displayLoginInformation(
			$session->user_agent,
			$session->login_date,
			$button
		);
	}

	// }}}
	// {{{ protected function displayLoginInformation()

	protected function displayLoginInformation($user_agent,
		SwatDate $login_date, SwatWidget $logout_button = null)
	{
		$locale = SwatI18NLocale::get();

		$now = new SwatDate();
		$now->toUTC();

		$interval = $now->diff($login_date);

		echo '<li>';

		echo '<span class="session">';

		$device_span = new SwatHtmlTag('span');
		$device_span->class = 'session-device';
		$device_span->setContent(
			sprintf(
				Site::_('%s on %s'),
				$this->getBrowser($user_agent),
				$this->getOS($user_agent)
			)
		);
		$device_span->display();

		echo ' ';

		if ($interval->days < 1) {

			$date_span = new SwatHtmlTag('span');
			$date_span->class = 'session-date';
			$date_span->setContent(Site::_('(logged in less than a day ago)'));
			$date_span->display();

		} else {

			$date_span = new SwatHtmlTag('span');
			$date_span->class = 'session-date';
			$date_span->setContent(
				sprintf(
					Site::ngettext(
						'(logged in %s day ago)',
						'(logged in %s days ago)',
						$interval->days
					),
					$locale->formatNumber($interval->days)
				)
			);
			$date_span->display();

		}

		echo '</span>';

		if ($logout_button instanceof SwatWidget) {
			$logout_button->visible = true;
			$logout_button->display();
			$logout_button->visible = false;
		}

		echo '</li>';
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addBodyClass('account-sessions-page');
	}

	// }}}
}

?>
