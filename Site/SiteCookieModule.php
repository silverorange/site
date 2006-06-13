<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for cookies
 *
 * @package   Site
 * @copyright 2006 silverorange
 */
class SiteCookieModule extends SiteApplicationModule
{
	// {{{ public properties

	public $cookie_prefix;

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this module
	 */
	public function init()
	{
		$this->cookie_prefix = $this->app->id;
	}

	// }}}
	// {{{ public function setCookie()

	/**
	 * Sets a cookie
	 *
	 * @param string $name the name of the cookie to set.
	 * @param mixed $value the value of the cookie.
	 */
	public function setCookie($name, $value)
	{

	}

	// }}}
	// {{{ private function __isset()

	/**
	 * Checks the existence of a cookie
	 *
	 * @param string $name the name of the cookie to check.
	 */
	private function __isset($name)
	{
		return isset($_COOKIE[$name]);
	}

	// }}}
	// {{{ private function &__get()

	/**
	 * Gets a cookie value
	 *
	 * @param string $name the name of the cookie to get.
	 *
	 * @return mixed the value of the cookie. This is returned by reference.
	 */
	private function &__get($name)
	{
		if (!isset($_COOKIE[$name]))
			throw new SiteException("Cookie '$name' is not set.");

		return $_COOKIE[$name];
	}

	// }}}
}

?>
