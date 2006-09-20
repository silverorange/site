<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for sessions
 *
 * @package   Site
 * @copyright 2006 silverorange
 */
class SiteSessionModule extends SiteApplicationModule
{
	// {{{ public function init()

	/**
	 * Initializes this session module
	 */
	public function init()
	{
		$session_name = $this->app->id;

		session_cache_limiter('');
		session_name($session_name);

		if (isset($_GET[$session_name]) ||
			isset($_POST[$session_name]) ||
			isset($_COOKIE[$session_name]))
				$this->activate();
	}

	// }}}
	// {{{ public function activate()

	/**
	 * Activates the current user's session
	 *
	 * Subsequent calls to the {@link isActive()} method will return true.
	 */
	public function activate()
	{
		if ($this->isActive())
			return;

		$this->startSession();
	}

	// }}}
	// {{{ public function isActive()

	/**
	 * Checks if there is an active session
	 *
	 * @return boolean true if session is active, false if the session is
	 *                  inactive.
	 */
	public function isActive()
	{
		return (strlen(session_id()) > 0);
	}

	// }}}
	// {{{ public function getSessionID()

	/**
	 * Retrieves the current session ID
	 *
	 * @return integer the current session ID, or null if no active session.
	 */
	public function getSessionID()
	{
		if (!$this->isActive())
			return null;

		return session_id();
	}

	// }}}
	// {{{ public function clear()

	/**
	 * Clears all session variables while maintaining the current session
	 */
	public function clear()
	{
		if (!$this->isActive())
			$_SESSION = array();
	}

	// }}}
	// {{{ public function appendSessionID()

	/**
	 * Appends the session ID to a URI
	 *
	 * Appends the session identifier (SID) to the URI if necessary.
	 * This method is called by SiteApplication::relocate() before relocating
	 * to the URI.
	 *
	 * @param string $uri the URI to be relocated to.
	 * @param boolean $append_sid whether to append sid to URI. If null, this
	 *                             is determined internally.
	 */
	public function appendSessionID($uri, $append_sid = null)
	{
		if ($this->isActive()) {

			if ($append_sid === null) {
				$is_relative_uri = (strpos($uri, '://') === false);
				$has_cookie = isset($_COOKIE[$this->app->id]);
				$append_sid = $is_relative_uri && !$has_cookie;
			}

			if ($append_sid) {
				$sid = sprintf('%s=%s', $this->app->id, $this->getSessionID());

				if (strpos($uri, $sid) === false) {
					if (strpos($uri, '?') === false)
						$uri.= '?';
					else
						$uri.= '&';

					$uri.= sprintf('%s=%s', $this->app->id, $this->getSessionID());
				}
			}
		}

		return $uri;
	}

	// }}}
	// {{{ public function setSavePath()

	/**
	 * Set the session save path
	 */
	public function setSavePath($path)
	{
		session_save_path($path);
	}

	// }}}
	// {{{ protected function startSession()

	/**
	 * Starts a session
	 */
	protected function startSession()
	{
		session_start();
	}

	// }}}
	// {{{ private function __set()

	/**
	 * Sets a session variable
	 *
	 * @param string $name the name of the session variable to set.
	 * @param mixed $value the value to set the variable to.
	 */
	private function __set($name, $value)
	{
		if (!$this->isActive())
			throw new SiteException('Session is  not active.');

		$_SESSION[$name] = $value;
	}

	// }}}
	// {{{ private function __isset()

	/**
	 * Checks the existence of a session variable
	 *
	 * If the session is not active this method always returns false.
	 *
	 * @param string $name the name of the session variable to check.
	 */
	private function __isset($name)
	{
		return ($this->isActive() && isset($_SESSION[$name]));
	}

	// }}}
	// {{{ private function __unset()

	/**
	 * Removes a session variable
	 *
	 * @param string $name the name of the session variable to set.
	 */
	private function __unset($name)
	{
		if (!$this->isActive())
			throw new SiteException('Session is not active.');

		if (isset($_SESSION[$name]))
			unset($_SESSION[$name]);
	}

	// }}}
	// {{{ private function &__get()

	/**
	 * Gets a session variable
	 *
	 * @param string $name the name of the session variable to get.
	 *
	 * @return mixed the session variable value. This is returned by reference.
	 */
	private function &__get($name)
	{
		if (!$this->isActive())
			throw new SiteException('Session is not active.');

		if (!isset($_SESSION[$name]))
			throw new SiteException("Session variable '{$name}' is not set.");

		return $_SESSION[$name];
	}

	// }}}
}

?>
