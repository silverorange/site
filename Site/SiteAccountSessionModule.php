<?php

require_once 'Site/SiteSessionModule.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/exceptions/SiteException.php';
require_once 'Site/dataobjects/SiteAccount.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatForm.php';
require_once 'Swat/SwatString.php';

/**
 * Web application module for sessions with accounts.
 *
 * Provides methods for account login/logout and accessing dataobjects in the
 * session.
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountSessionModule extends SiteSessionModule
{
	// {{{ private properties

	private $data_object_classes = array();

	// }}}
	// {{{ protected properties

	protected $login_callbacks = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new store session module
	 *
	 * @param SiteApplication $app the application this module belongs to.
	 *
	 * @throws SiteException if there is no database module loaded the session
	 *                         module throws an exception.
	 *
	 */
	public function __construct(SiteApplication $app)
	{
		$this->registerActivateCallback(
			array($this, 'regenerateAuthenticationToken'));

		$this->registerRegenerateIdCallback(
			array($this, 'regenerateAuthenticationToken'));

		parent::__construct($app);
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The site account session module depends on the SiteDatabaseModule
	 * feature.
	 *
	 * @return array an array of {@link SiteApplicationModuleDependency}
	 *                        objects defining the features this module
	 *                        depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();
		$depends[] = new SiteApplicationModuleDependency('SiteDatabaseModule');
		return $depends;
	}

	// }}}
	// {{{ public function login()

	/**
	 * Logs the current session into a {@link SiteAccount}
	 *
	 * @param string $email the email address of the account to login.
	 * @param string $password the password of the account to login.
	 *
	 * @return boolean true if the account was successfully logged in and false
	 *                       if the email/password pair did not match an
	 *                       account.
	 */
	public function login($email, $password)
	{
		if ($this->isLoggedIn())
			$this->logout();

		$account = $this->getNewAccountObject();

		$instance = ($this->app->hasModule('SiteMultipleInstanceModule')) ?
			$this->app->instance->getInstance() : null;

		if ($account->loadWithCredentials($email, $password, $instance)) {
			$this->activate();
			$this->account = $account;

			$this->regenerateId();
			$this->setAccountCookie();
			$this->runLoginCallbacks();

			// save last login date
			$now = new SwatDate();
			$now->toUTC();
			$this->account->updateLastLoginDate($now);
		}

		return $this->isLoggedIn();
	}

	// }}}
	// {{{ public function loginById()

	/**
	 * Logs the current session into a {@link SiteAccount} using an id
	 *
	 * @param integer $id The id of the {@link SiteAccount} to log into.
	 *
	 * @return boolean true if the account was successfully logged in and false
	 *                       if the id does not match an account.
	 */
	public function loginById($id)
	{
		if ($this->isLoggedIn())
			$this->logout();

		$account = $this->getNewAccountObject();

		if ($account->load($id)) {
			$this->activate();
			$this->account = $account;

			$this->regenerateId();
			$this->setAccountCookie();
			$this->runLoginCallbacks();

			// save last login date
			$now = new SwatDate();
			$now->toUTC();
			$this->account->updateLastLoginDate($now);
		}

		return $this->isLoggedIn();
	}

	// }}}
	// {{{ public function loginByAccount()

	/**
	 * Logs the current session into the specified {@link SiteAccount}
	 *
	 * @param SiteAccount $account The account to log into.
	 *
	 * @return boolean true.
	 */
	public function loginByAccount(SiteAccount $account)
	{
		if ($this->isLoggedIn())
			$this->logout();

		$this->activate();
		$this->account = $account;
		$this->regenerateId();
		$this->setAccountCookie();
		$this->runLoginCallbacks();

		// save last login date
		$now = new SwatDate();
		$now->toUTC();
		$this->account->updateLastLoginDate($now);

		return true;
	}

	// }}}
	// {{{ public function logout()

	/**
	 * Logs the current user out
	 */
	public function logout()
	{
		unset($this->account);
		unset($this->_authentication_token);
		$this->removeAccountCookie();
	}

	// }}}
	// {{{ public function isLoggedIn()

	/**
	 * Checks the current user's logged-in status
	 *
	 * @return boolean true if user is logged in, false if the user is not
	 *                  logged in.
	 */
	public function isLoggedIn()
	{
		if (!$this->isActive())
			return false;

		if (!isset($this->account))
			return false;

		if ($this->account === null)
			return false;

		if ($this->account->id === null)
			return false;

		return true;
	}

	// }}}
	// {{{ public function getAccountId()

	/**
	 * Retrieves the current account Id
	 *
	 * @return integer the current account ID, or null if not logged in.
	 */
	public function getAccountId()
	{
		if (!$this->isLoggedIn())
			return null;

		return $this->account->id;
	}

	// }}}
	// {{{ public function registerDataObject()

	/**
	 * Register a dataobject class for a session variable
	 *
	 * @param string $name the name of the session variable
	 * @param string $class the dataobject class name
	 */
	public function registerDataObject($name, $class)
	{
		$this->data_object_classes[$name] = $class;
	}

	// }}}
	// {{{ public function registerLoginCallback()

	/**
	 * Registers a callback function that is executed when a successful session
	 * login is performed
	 *
	 * @param callback $callback the callback to call when a successful login
	 *                            is performed.
	 * @param array $parameters optional. The paramaters to pass to the
	 *                           callback. Use an empty array for no parameters.
	 */
	public function registerLoginCallback($callback,
		array $parameters = array())
	{
		if (!is_callable($callback))
			throw new SiteException('Cannot register invalid callback.');

		$this->login_callbacks[] = array(
			'callback' => $callback,
			'parameters' => $parameters
		);
	}

	// }}}
	// {{{ protected function regenerateAuthenticationToken()

	protected function regenerateAuthenticationToken()
	{
		$this->_authentication_token = SwatString::hash(mt_rand());
		SwatForm::setAuthenticationToken($this->_authentication_token);
	}

	// }}}
	// {{{ protected function startSession()

	/**
	 * Starts a session
	 */
	protected function startSession()
	{
		// make sure dataobject classes are loaded before starting the session
		foreach ($this->data_object_classes as $name => $class) {
			if (!class_exists($class))
				throw new SiteException("Class {$class} does not exist. ".
					'The class must be loaded before it can be registered '.
					'in the session.');
		}

		session_start();

		foreach ($this->data_object_classes as $name => $class) {
			if (isset($this->$name) && $this->$name !== null) {
				if (is_array($this->$name) ||
					$this->$name instanceof ArrayObject) {
					foreach ($this->$name as $object) {
						$object->setDatabase(
							$this->app->database->getConnection());
					}
				} else {
					$this->$name->setDatabase(
						$this->app->database->getConnection());
				}
			} else {
				$this->$name = null;
			}
		}

		if (isset($this->_authentication_token))
			SwatForm::setAuthenticationToken($this->_authentication_token);
	}

	// }}}
	// {{{ protected function getNewAccountObject()

	protected function getNewAccountObject()
	{
		$class_name = SwatDBClassMap::get('SiteAccount');
		$account = new $class_name();
		$account->setDatabase($this->app->db);

		return $account;
	}

	// }}}
	// {{{ protected function runLoginCallbacks()

	protected function runLoginCallbacks()
	{
		foreach ($this->login_callbacks as $login_callback) {
			$callback = $login_callback['callback'];
			$parameters = $login_callback['parameters'];
			call_user_func_array($callback, $parameters);
		}
	}

	// }}}
	// {{{ protected function setAccountCookie()

	protected function setAccountCookie()
	{
		if (!isset($this->app->cookie))
			return;

		$this->app->cookie->setCookie('account_id', $this->getAccountId());
	}

	// }}}
	// {{{ protected function removeAccountCookie()

	protected function removeAccountCookie()
	{
		if (!isset($this->app->cookie))
			return;

		$this->app->cookie->removeCookie('account_id');
	}

	// }}}
}

?>
