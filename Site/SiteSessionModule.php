<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Site/exceptions/SiteSessionHijackException.php';

/**
 * Web application module for sessions
 *
 * @package   Site
 * @copyright 2006 silverorange
 */
class SiteSessionModule extends SiteApplicationModule
{
	// {{{ protected properties

	protected $regenerate_id_callbacks = array();

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this session module
	 */
	public function init()
	{
		$session_name = $this->getSessionName();

		session_cache_limiter('');
		session_name($session_name);

		if ($this->shouldAutoActivateSession())
			$this->autoActivate();
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

		/*
		 * Store the user agent in the session to mitigate the risk of session
		 * hijacking. See the autoActivate() method for details.
		 */
		if (isset($_SERVER['HTTP_USER_AGENT']))
			$this->_user_agent = $_SERVER['HTTP_USER_AGENT'];
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
	// {{{ public function getSessionId()

	/**
	 * Retrieves the current session ID
	 *
	 * @return integer the current session ID, or null if no active session.
	 */
	public function getSessionId()
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
	// {{{ public function appendSessionId()

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
	public function appendSessionId($uri, $append_sid = null)
	{
		if ($this->isActive()) {

			if ($append_sid === null) {
				$is_relative_uri = (strpos($uri, '://') === false);
				$has_cookie = isset($_COOKIE[$this->getSessionName()]);
				$append_sid = $is_relative_uri && !$has_cookie;
			}

			if ($append_sid) {
				$sid = sprintf('%s=%s', $this->getSessionName(),
					$this->getSessionId());

				if (strpos($uri, $sid) === false) {
					if (strpos($uri, '?') === false)
						$uri.= '?';
					else
						$uri.= '&';

					$uri.= sprintf('%s=%s', $this->getSessionName(),
						$this->getSessionId());
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
	// {{{ public function autoActivate()

	/**
	 * Activates the current user's session
	 *
	 * Subsequent calls to the {@link isActive()} method will return true.
	 */
	public function autoActivate()
	{
		if ($this->isActive())
			return;

		$this->startSession();

		/*
		 * The user agent is stored in the session to mitigate the risk of
		 * session hijacking. When a future request presents this session's ID
		 * to us we will only activate the session if the user agent matches.
		 * While this is not fool-proof it does mitigate the most common
		 * accidently and malicous session hijacking attempts.
		 */
		if (isset($_SERVER['HTTP_USER_AGENT']) && isset($this->_user_agent) &&
			$this->_user_agent !== $_SERVER['HTTP_USER_AGENT']) {

			$session_name = $this->getSessionName();
			if (isset($_GET[$session_name]))
				$this->app->relocate($this->app->getUri(), null, false);

			throw new SiteSessionHijackException($this->_user_agent);
		}
	}

	// }}}
	// {{{ public function registerRegenerateIdCallback()

	/**
	 * Registers a callback function that is executed when the session ID
	 * is regenerated
	 *
	 * @param callback $callback the callback to call when regenerating session
	 *                            ID.
	 * @param array $parameters the paramaters to pass to the callback. Use an
	 *                           empty array for no parameters.
	 */
	public function registerRegenerateIdCallback($callback, $parameters = array())
	{
		if (!is_callable($callback))
			throw new SiteException('Cannot register invalid callback.');

		if (!is_array($parameters))
			throw new SiteException('Callback parameters must be specified '.
				'in an array.');

		$this->regenerate_id_callbacks[] = array(
			'callback' => $callback,
			'parameters' => $parameters
		);
	}

	// }}}
	// {{{ public function regenerateId()

	public function regenerateId()
	{
		$old_id = $this->getSessionId();
		session_regenerate_id();
		$new_id = $this->getSessionId();

		foreach ($this->regenerate_id_callbacks as $callback) {
			$function = $callback['callback'];
			$parameters = $callback['parameters'];
			array_unshift($parameters, $old_id, $new_id);
			call_user_func_array($function, $parameters);
		}
	}

	// }}}
	// {{{ protected function shouldAutoActivateSession()

	/**
	 * Whether to auto activate a session during module init
	 */
	protected function shouldAutoActivateSession()
	{
		if (isset($_SERVER['REDIRECT_STATUS'])) {
			switch ($_SERVER['REDIRECT_STATUS']) {
			case '403':
			case '504':
			case '500':
				return false;
			}
		}

		$session_name = $this->getSessionName();

		if (isset($_GET[$session_name]) ||
			isset($_POST[$session_name]) ||
			isset($_COOKIE[$session_name]))
				return true;

		return false;
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
	// {{{ public function getSessionName()

	/**
	 * Retrieves the session name
	 *
	 * @return string the name to use for the session.
	 */
	public function getSessionName()
	{
		return $this->app->id;
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
