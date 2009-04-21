<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for testing alternatives.
 *
 * @package   Site
 * @copyright 2008-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAlternativeTestModule extends SiteApplicationModule
{
	// {{{ private properties

	private $test_values = array();

	// }}}
	// {{{ public function init()

	public function init()
	{
		if ($this->app->session->isActive())
			if (!isset($this->app->session->alternative_tests))
				$this->app->session->alternative_tests = array();

		foreach ($this->test_values as $name => $value)
			$this->initTest($name);
	}

	// }}}
	// {{{ public function registerTest()

	public function registerTest($name, $default_value)
	{
		$this->test_values[$name] = $default_value;
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Get the modules this module depends on
	 *
	 * The site alternative test module depends on the SiteSessionModule.
	 *
	 * @return array an array of {@link SiteApplicationModuleDependency}
	 *                        objects defining the features this module
	 *                        depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();
		$depends[] = new SiteApplicationModuleDependency('SiteSessionModule');
		$depends[] = new SiteApplicationModuleDependency('SiteCookieModule');
		return $depends;
	}

	// }}}
	// {{{ public function __get()

	public function __get($name)
	{
		if (!array_key_exists($name, $this->test_values)) {
			throw new SiteException(sprintf(
				"Test '%s' does not exist.", $name));
		}

		return $this->test_values[$name];
	}

	// }}}
	// {{{ protected function initTest()

	protected function initTest($name)
	{
		if ($this->app->session->isActive() &&
			isset($this->app->session->alternative_tests[$name])) {
				$value = $this->app->session->alternative_tests[$name];
				$this->app->cookie->removeCookie($name);

		} elseif (isset($this->app->cookie->$name)) {
			$value = $this->app->cookie->$name;

		} else {
			$value = (rand(0,1) === 1);
			$this->app->cookie->setCookie($name, $value, 0);
		}

		if ($this->app->session->isActive())
			$this->app->session->alternative_tests[$name] = $value;

		$this->test_values[$name] = $value;
	}

	// }}}
	// {{{ public function getAll()

	public function getAll()
	{
		return $this->test_values;
	}

	// }}}
}

?>
