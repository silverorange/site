<?php

require_once 'Site/pages/SiteUiPage.php';

/**
 * Page for listing current persistent login sessions
 *
 * @package   Site
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAccount
 * @see       SiteAccountLoginTag
 */
class SiteAccountSessionsPage extends SiteUiPage
{
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
		if (!$this->app->session->isLoggedIn()) {
			$this->app->relocate('account/login');
		}

		parent::init();
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		ob_start();

		echo '<div class="account-sessions-current">';

		$header = new SwatHtmlTag('h3');
		$header->setContent(Site::_('Current Session'));
		$header->display();

		echo '<ul class="account-sessions-current-list">';

		$this->displayCurrentSession();

		echo '</ul>';

		echo '</div>';

		$cookie_tag = (isset($this->app->cookie->login))
			? $this->app->cookie->login
			: null;

		$count = 0;
		foreach ($this->app->session->account->login_tags as $login_tag) {
			if ($login_tag->tag !== $cookie_tag) {
				$count++;
			}
		}

		if ($count > 0) {
			echo '<div class="account-sessions-other">';

			$header = new SwatHtmlTag('h3');
			$header->setContent(Site::_('Active Sessions on Other Devices'));
			$header->display();

			echo '<ul class="account-sessions-other-list">';

			foreach ($this->app->session->account->login_tags as $login_tag) {
				if ($login_tag->tag !== $cookie_tag) {
					$this->displayLoginTag($login_tag);
				}
			}

			echo '</ul>';

			echo '</div>';
		}

		$this->ui->getWidget('sessions_content')->content = ob_get_clean();
		$this->ui->getWidget('sessions_content')->content_type = 'text/xml';
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
	// {{{ protected function displayCurrentSession()

	protected function displayCurrentSession()
	{

		$current_login = null;
		$cookie_tag = (isset($this->app->cookie->login))
			? $this->app->cookie->login
			: null;

		if ($cookie_tag !== null) {
			foreach ($this->app->session->account->login_tags as $login_tag) {
				if ($login_tag->tag === $cookie_tag) {
					$current_login = $login_tag;
					break;
				}
			}
		}

		if ($current_login instanceof SiteAccountLoginTag) {

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


		$this->displayLoginInformation($user_agent, $login_date, false);
	}

	// }}}
	// {{{ protected function displayLoginTag()

	protected function displayLoginTag(SiteAccountLoginTag $login_tag)
	{
		$this->displayLoginInformation(
			$login_tag->user_agent,
			$login_tag->login_date
		);
	}

	// }}}
	// {{{ protected function displayLoginInformation()

	protected function displayLoginInformation($user_agent,
		SwatDate $login_date, $logout = true)
	{
		$locale = SwatI18NLocale::get();

		$now = new SwatDate();
		$now->toUTC();

		$interval = $now->diff($login_date);

		echo '<li>';

		if ($interval->days < 1) {

			$span = new SwatHtmlTag('span');
			$span->setContent(
				sprintf(
					Site::_('%s on %s (logged in less than a day ago)'),
					$this->getBrowser($user_agent),
					$this->getOS($user_agent)
				)
			);

		} else {

			$span = new SwatHtmlTag('span');
			$span->setContent(
				sprintf(
					Site::ngettext(
						'%s on %s (logged in %s day ago)',
						'%s on %s (logged in %s days ago)',
						$interval->days
					),
					$this->getBrowser($user_agent),
					$this->getOS($user_agent),
					$locale->formatNumber($interval->days)
				)
			);

		}

		$span->display();

		if ($logout) {
			echo '<input type="submit" class="button compact-button gray-button" value="End Session" />';
		}

		echo '</li>';
	}

	// }}}
}

?>
