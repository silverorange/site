<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for testing alternatives.
 *
 * @package   Site
 * @copyright 2008 silverorange
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
		if ($this->app->session->isActive()) {
			if (!isset($this->app->session->alternative_tests))
				$this->app->session->alternative_tests = array();

			foreach ($this->test_values as $name => $value)
				$this->initTest($name);
		}
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
		return $depends;
	}

	// }}}
	// {{{ protected function initTest()

	protected function initTest($name)
	{
		if (!isset($this->app->session->alternative_tests[$name])) {
			$value = (rand(0,1) === 1);
			$this->app->session->alternative_tests[$name] = $value;
		}

		$this->test_values[$name] = $this->app->session->alternative_tests[$name];
	}

	// }}}
	// {{{ private function __get()

	private function __get($name)
	{
		if (!array_key_exists($name, $this->test_values)) {
			throw new SiteException(sprintf(
				"Test '%s' does not exist.", $name));
		}

		return $this->test_values[$name];
	}

	// }}}
}

?>
