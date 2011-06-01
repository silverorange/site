<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for testing alternatives.
 *
 * @package   Site
 * @copyright 2008-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link      http://code.google.com/apis/analytics/docs/tracking/gaTrackingCustomVariables.html
 */
class SiteAlternativeTestModule extends SiteApplicationModule
{
	// {{{ private properties

	private $tests = array();
	private $slots = array();

	// }}}
	// {{{ public function init()

	public function init()
	{
		foreach ($this->tests as $name => $value) {
			$this->initTest($name);
		}
	}

	// }}}
	// {{{ public function registerTest()

	public function registerTest($name, $default_value, $slot = null)
	{
		$this->tests[$name] = $default_value;

		// Slots are used by GA custom variables, hence slots only matter if we
		// have an Analytics Module
		if ($this->app->hasModule('SiteAnalyticsModule')) {
			if ($slot == null) {
				$slot = (count($this->slots) + 1);
			}

			if ($slot > SiteAnalyticsModule::CUSTOM_VARIABLE_SLOTS) {
				throw new SiteException(sprintf(
					'Concurrent test limit of %s exceeded.',
					SiteAnalyticsModule::CUSTOM_VARIABLE_SLOTS));
			}

			$this->slots[$name] = $slot;
		}
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Get the modules this module depends on
	 *
	 * The site alternative test module depends on the SiteCookieModule.
	 *
	 * @return array an array of {@link SiteApplicationModuleDependency}
	 *                        objects defining the features this module
	 *                        depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();
		$depends[] = new SiteApplicationModuleDependency('SiteCookieModule');
		return $depends;
	}

	// }}}
	// {{{ public function __get()

	public function __get($name)
	{
		if (!array_key_exists($name, $this->tests)) {
			throw new SiteException(sprintf(
				"Test '%s' does not exist.", $name));
		}

		// value is stored as string 1 or 0.
		return ($this->tests[$name] == '1' ? true : false);
	}

	// }}}
	// {{{ public function getAll()

	public function getAll()
	{
		return $this->tests;
	}

	// }}}
	// {{{ protected function initTest()

	protected function initTest($name)
	{
		$value       = null;
		$set_cookie  = false;
		$cookie_name = 'test_'.$name;

		if (isset($_GET['alternatetest']) && $_GET['alternatetest'] == $name
			&& isset($_GET['alternatetestversion'])) {
			// Cast as boolean so it matches expected values. This should change
			// if we ever do more than a/b testing.
			$value = (bool) $_GET['alternatetestversion'];
			$set_cookie = true;
		} elseif (isset($_GET[$name.'_test'])) {
			// Cast as boolean so it matches expected values. This should change
			// if we ever do more than a/b testing.
			$value = (bool) $_GET[$name.'_test'];
			$set_cookie = true;
		} else {
			if (isset($this->app->cookie->$cookie_name)) {
				$value = (bool) $this->app->cookie->$cookie_name;
			} else {
				$value = (mt_rand(0, 1) === 1);
				$set_cookie = true;
			}
		}

		// store value as string since a boolean false would delete the cookie.
		$value = ($value === true) ? '1' : '0';

		if ($set_cookie === true) {
			// Set the cookie for 2 years, just like the google analytics
			// cookie. This is overkill, but matches how we're tracking it in
			// Google Analytics
			$this->app->cookie->setCookie($cookie_name, $value,
				strtotime('+2 Years'));
		}

		$this->tests[$name] = $value;

		if ($this->app->hasModule('SiteAnalyticsModule')) {
			$analytics = $this->app->getModule('SiteAnalyticsModule');

			$slot = $this->slots[$name];
			// Name and Value have a 64 byte limit according to google
			// documentation. Since currently value is always 1 chars, limit
			// name to 63 chars.
			$name = substr($name, 0, 63);

			$ga_command = array(
				'_setCustomVar',
				$slot,
				$name,
				$value,
				SiteAnalyticsModule::CUSTOM_VARIABLE_SCOPE_VISITOR,
				);

			// prepend since it has to happen before the pageview
			$analytics->prependGoogleAnalyticsCommand($ga_command);
		}
	}

	// }}}
}

?>
