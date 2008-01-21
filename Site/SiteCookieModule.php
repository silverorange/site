<?php

require_once 'Swat/SwatString.php';
require_once 'Site/SiteApplicationModule.php';
require_once 'Site/exceptions/SiteCookieException.php';

/**
 * Web application module for cookies
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 */
class SiteCookieModule extends SiteApplicationModule
{
	// {{{ private properties

	private $salt = '';

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
	// {{{ public function setSalt()

	/**
	 * Set salt
	 */
	public function setSalt($salt)
	{
		$this->salt = $salt;
	}

	// }}}
	// {{{ public function setCookie()

	/**
	 * Sets a cookie
	 *
	 * @param string $name the name of the cookie to set.
	 * @param mixed $value the value of the cookie.
	 * @param mixed $expiry the expiry date as a UNIX timestamp or a
	                         string parsable by strtotime().
	 * @param string $path the URL path this cookie is valid for.
	 * @param string $domain the domain this cookie is valid for.
	 */
	public function setCookie($name, $value, $expiry = null,
		$path = '/', $domain = null)
	{
		$name = $this->getPrefix().'_'.$name;

		if ($expiry === null)
			$expiry = strtotime('+90 days');
		elseif (is_string($expiry))
			$expiry = strtotime($expiry);

		$cookie_value = SwatString::signedSerialize($value, $this->salt);

		// TODO: get domain from application
		//if ($domain = null)
		//	$domain = 

		setcookie($name, $cookie_value, $expiry, $path);
		//setcookie($name, $value, $expiry, $path, $domain);
	}

	// }}}
	// {{{ public function removeCookie()

	/**
	 * Remove a cookie
	 *
	 * @param string $name the name of the cookie to set.
	 * @param string $path the URL path this cookie is valid for.
	 * @param string $domain the domain this cookie is valid for.
	 */
	public function removeCookie($name, $path = '/', $domain = null)
	{
		$name = $this->getPrefix().'_'.$name;
		$expiry = time() - 3600;

		// TODO: get domain from application
		//if ($domain = null)
		//	$domain = 

		setcookie($name, 0, $expiry, $path);
		//setcookie($name, 0, $expiry, $path, $domain);

		unset($_COOKIE[$name]);
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * @return array an array of {@link SiteModuleDependency} objects defining
	 *                        the features this module depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();

		if ($this->app->hasModule('SiteMultipleInstanceModule'))
			$depends[] = new SiteApplicationModuleDependency(
				'SiteMultipleInstanceModule');

		return $depends;
	}

	// }}}
	// {{{ protected function getPrefix()

	/**
	 * Gets the prefix for the cookie name
	 *
	 * @return string Cookie prefix
	 */
	protected function getPrefix()
	{
		$prefix = $this->app->id;

		if ($this->app->hasModule('SiteMultipleInstanceModule') &&
			$this->app->instance->getInstance() !== null) {
			$prefix = $this->app->instance->getInstance()->shortname;
		}

		return $prefix;
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
		$name = $this->getPrefix().'_'.$name;

		if (!isset($_COOKIE[$name]))
			throw new SiteCookieException("Cookie '{$name}' is not set.");

		try {
			$value =
				SwatString::signedUnserialize($_COOKIE[$name], $this->salt);
		} catch (SwatInvalidSerializedDataException $e) {
			// Try old unsalt technique for backwards compatibility
			// this will be removed in future versions of Site
			$value = $this->unsaltValue($_COOKIE[$name]);

			// If we unserilaized an old cookie value successfully,
			// reserialize it using the newer, safer method.
			$this->setCookie($name, $value);
		}

		return $value;
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
		$name = $this->getPrefix().'_'.$name;
		return isset($_COOKIE[$name]);
	}

	// }}}
	// {{{ private function unsaltValue()

	/**
	 * Unsalts and verifies a cookie value
	 *
	 * This method is unsafe because the value is unserialized before its
	 * signature is checked. This method exists to provide backwards
	 * compatibility and will be removed in later versions of Site.
	 *
	 * @throws SiteCookieException if the cookie data was tampered with by the
	 *                              user.
	 *
	 * @deprecated This method only exists for backwards compatibility with
	 *             the old, less secure data-signing technique. It will be
	 *             removed in future versions of SiteCookieModule.
	 */
	private function unsaltValue($value)
	{
		$package = @unserialize($value);

		// serialized value is broken
		if ($package === false && strcmp($value, serialize(false)) != 0)
			throw new SiteCookieException('Could not unserialize cookie.');

		// serialized data is not how we expected
		if (!is_array($package) || !isset($package['value']) ||
			!isset($package['hash']))
				throw new SiteCookieException('Corrupt cookie data.');

		$value = $package['value'];

		// hash does not match, cookie was tampered with
		if ($package['hash'] !== $this->getHash($value))
			throw new SiteCookieException('Invalid cookie hash.');
	}

	// }}}
	// {{{ private function getHash()

	/**
	 * Gets the hash value for a cookie value
	 *
	 * @deprecated This method only exists for backwards compatibility with
	 *             the old, less secure data-signing technique. It will be
	 *             removed in future versions of SiteCookieModule.
	 */
	private function getHash($value)
	{
		return md5($this->salt.serialize($value));
	}

	// }}}
}

?>
