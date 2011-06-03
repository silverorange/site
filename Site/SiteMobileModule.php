<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Site/exceptions/SiteException.php';

/**
 * Web application module for working with mobile devices
 *
 * This module provides two primary functions. One is to detect, using
 * the user-agent string, whether or not a browser appears to be on a
 * mobile device. The second function is to automatically switch to a
 * mobile URL for mobile browsers. The module's isMobileUrl() method can
 * also be used to detect if the site is the mobile version.
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMobileModule extends SiteApplicationModule
{
	// {{{ protected properties

	protected $prefix = 'm';
	protected $get_var = 'mobile';
	protected $switch_get_var = 'mobile-version';

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this cookie module
	 *
	 * No initilization tasks are performed for the cookie module.
	 */
	public function init()
	{
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The site memcached module optionally depends on the
	 * SiteMultipleInstanceModule feature.
	 *
	 * @return array an array of {@link SiteModuleDependency} objects defining
	 *                        the features this module depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();

		$depends[] = new SiteApplicationModuleDependency(
			'SiteCookieModule', true);

		return $depends;
	}

	// }}}
	// {{{ public function getPrefix()

	/**
	 * Get the URL prefix for a mobile URL
	 *
	 * By default, 'm', e.g. http://domain.com/m/path.
	 *
	 * @return string The prefix for the mobile URL
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

	// }}}
	// {{{ public function getSwitchGetVar()

	/**
	 * Get the variable to append to the URL when switching to the mobile
	 * version of the site.
	 *
	 * This lets us tell the switch was explicit as opposed to an automatic
	 * redirect via SiteMobileModule::autoRelocateMobile().
	 *
	 * @return string The GET var
	 */
	public function getSwitchGetVar()
	{
		return $this->switch_get_var;
	}

	// }}}
	// {{{ public function isMobileUrl()

	/**
	 * Detect whether or not we're on the mobile version of the site
	 *
	 * When on the mobile version of the site, a GET var must be set
	 * indicating it's the mobile version.
	 *
	 * @return boolean True if its the mobile site
	 */
	public function isMobileUrl()
	{
		return (isset($_GET[$this->get_var]));
	}

	// }}}
	// {{{ public function autoRelocateMobile()

	/**
	 * Check whether the user should be redirected to the mobile version
	 *
	 * Usually this method would be called at the top of
	 * SiteApplication::resolvePage(), after the modules have be initiated,
	 * but before the page is loaded.
	 *
	 * The redirection works as follows:
	 *  - First check to see if we should even attempt to redirect the user.
	 *  - Then check if the user has explicitly indicated (via clicking a
	 *    link whether they want the mobile or non-mobile sites)
	 *  - Switch to the mobile site if the user is using a mobile device
	 *
	 * @param string $source Optional source to relocate to
	 */
	public function autoRelocateMobile($source = '')
	{
		$has_mobile_access = $this->attemptMobileRelocate();
		$try_mobile = true;

		// if we can view the mobile site, check for the mobile cookie or the
		// mobile get var
		if ($has_mobile_access) {
			if (isset($_GET[$this->switch_get_var])) {
				$try_mobile = (boolean) $_GET[$this->switch_get_var];
				$this->app->cookie->setCookie('mobile_enabled', $try_mobile);
			} elseif (!isset($this->app->cookie->mobile_enabled) ||
				$this->app->cookie->mobile_enabled) {
				// try by default if neither the cookie nor the get var are set
				$try_mobile = true;
			}
		}

		if ($this->isMobileUrl()) {
			if ($has_mobile_access) {
				$this->app->cookie->setCookie('mobile_enabled', true);
			} else {
				// go to non-mobile site if mobile is unsupported
				$this->app->relocate($this->app->getSwitchMobileLink(false,
					$source));
			}
		} elseif ($try_mobile && $this->isMobileBrowser()) {
			// try to go to mobile site
			$this->app->relocate($this->app->getSwitchMobileLink(true,
				$source));
		}
	}

	// }}}
	// {{{ public function isMobileBrowser()

	public function isMobileBrowser()
	{
		// regexp from http://detectmobilebrowser.com/
		$useragent = $_SERVER['HTTP_USER_AGENT'];

		// cache match to make it a little faster
		static $match;
		if ($match !== null) {
			return $match;
		}

		$match = (
			preg_match('
				/
				android|
				avantgo|
				blackberry|
				blazer|
				compal|
				elaine|
				fennec|
				hiptop|
				iemobile|
				ip(hone|od)|
				iris|
				kindle|
				lge\ |
				maemo|
				midp|
				mmp|
				opera\ m(ob|in)i|
				palm(\ os)?|
				phone|
				p(ixi|re)\/|
				plucker|
				pocket|
				psp|
				symbian|
				treo|
				up\.(browser|link)|
				vodafone|
				wap|
				windows\ (ce|phone)|
				xda|
				xiino
				/ix',
				$useragent) ||
			preg_match(
				'/
				1207|
				6310|
				6590|
				3gso|
				4thp|
				50[1-6]i|
				770s|
				802s|
				a\ wa|
				abac|
				ac(er|oo|s\-)|
				ai(ko|rn)|
				al(av|ca|co)|
				amoi|
				an(ex|ny|yw)|
				aptu|ar(ch|go)|
				as(te|us)|
				attw|au(di|\-m|r\ |s\ )|
				avan|be(ck|ll|nq)|
				bi(lb|rd)|
				bl(ac|az)|
				br(e|v)w|
				bumb|
				bw\-(n|u)|
				c55\/|
				capi|
				ccwa|
				cdm\-|
				cell|
				chtm|
				cldc|
				cmd\-|
				co(mp|nd)|
				craw|
				da(it|ll|ng)|
				dbte|
				dc\-s|
				devi|
				dica|
				dmob|
				do(c|p)o|ds(12|\-d)|
				el(49|ai)|
				em(l2|ul)|
				er(ic|k0)|
				esl8|ez([4-7]0|os|wa|ze)|
				fetc|
				fly(\-|_)|
				g1\ u|
				g560|
				gene|
				gf\-5|
				g\-mo|
				go(\.w|od)|
				gr(ad|un)|
				haie|
				hcit|
				hd\-(m|p|t)|
				hei\-|
				hi(pt|ta)|
				hp(\ i|ip)|
				hs\-c|
				ht(c(\-|\ |_|a|g|p|s|t)|tp)|
				hu(aw|tc)|
				i\-(20|go|ma)|
				i230|
				iac(\ |\-|\/)|
				ibro|
				idea|
				ig01|
				ikom|
				im1k|
				inno|
				ipaq|
				iris|
				ja(t|v)a|
				jbro|
				jemu|
				jigs|
				kddi|
				keji|
				kgt(\ |\/)|
				klon|
				kpt\ |
				kwc\-|
				kyo(c|k)|
				le(no|xi)|
				lg(\ g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|
				libw|
				lynx|
				m1\-w|
				m3ga|
				m50\/|
				ma(te|ui|xo)|
				mc(01|21|ca)|
				m\-cr|
				me(di|rc|ri)|
				mi(o8|oa|ts)|
				mmef|
				mo(01|02|bi|de|do|t(\-|\ |o|v)|zz)|
				mt(50|p1|v\ )|
				mwbp|
				mywa|
				n10[0-2]|
				n20[2-3]|
				n30(0|2)|
				n50(0|2|5)|
				n7(0(0|1)|10)|
				ne((c|m)\-|on|tf|wf|wg|wt)|
				nok(6|i)|
				nzph|
				o2im|
				op(ti|wv)|
				oran|
				owg1|
				p800|
				pan(a|d|t)|
				pdxg|
				pg(13|\-([1-8]|c))|
				phil|
				pire|
				pl(ay|uc)|
				pn\-2|
				po(ck|rt|se)|
				prox|
				psio|
				pt\-g|
				qa\-a|
				qc(07|12|21|32|60|\-[2-7]|i\-)|
				qtek|
				r380|
				r600|
				raks|
				rim9|
				ro(ve|zo)|
				s55\/|
				sa(ge|ma|mm|ms|ny|va)|
				sc(01|h\-|oo|p\-)|
				sdk\/|
				se(c(\-|0|1)|47|mc|nd|ri)|
				sgh\-|
				shar|
				sie(\-|m)|
				sk\-0|
				sl(45|id)|
				sm(al|ar|b3|it|t5)|
				so(ft|ny)|
				sp(01|h\-|v\-|v\ )|
				sy(01|mb)|
				t2(18|50)|
				t6(00|10|18)|
				ta(gt|lk)|
				tcl\-|
				tdg\-|
				tel(i|m)|
				tim\-|
				t\-mo|
				to(pl|sh)|
				ts(70|m\-|m3|m5)|
				tx\-9|
				up(\.b|g1|si)|
				utst|
				v400|
				v750|
				veri|
				vi(rg|te)|
				vk(40|5[0-3]|\-v)|
				vm40|
				voda|
				vulc|
				vx(52|53|60|61|70|80|81|83|85|98)|
				w3c(\-|\ )|
				webc|
				whit|
				wi(g\ |nc|nw)|
				wmlb|
				wonu|
				x700|
				xda(\-|2|g)|
				yas\-|
				your|
				zeto|
				zte\-
				/ix', substr($useragent, 0, 4)));

		return $match;
	}

	// }}}
	// {{{ protected function attemptMobileRelocate()

	/**
	 * Whether or not to try redirecting a user to the mobile site
	 *
	 * Mobile access can be disabled to users, for instance an administrator
	 * who always needs to see the standard version of the site.
	 *
	 * @return boolean
	 */
	protected function attemptMobileRelocate()
	{
		return true;
	}

	// }}}

}

?>
