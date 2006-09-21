<?php

require_once 'Site/layouts/SiteLayout.php';

/**
 * @package   Site
 * @copyright 2006 silverorange
 */
abstract class SitePageFactory
{
	// {{{ protected properties

	/**
	 * An array that maps other package classes to filenames
	 *
	 * The array is of the form:
	 *    package_prefix => path
	 * Where package prefix is the classname prefix used in this package and 
	 * path is the relative path where the source files for this package may 
	 * be included from.
	 *
	 * @var array
	 */
	protected $class_map = array('Swat' => 'Swat/pages');

	protected $page_class_path = '../include/pages';

	// }}}
	// {{{ abstract public static function instance()

	/**
	 * Gets the singleton instance of the factory object
	 *
	 * @return SitePageFactory the singleton instance.
	 */
	abstract public static function instance();

	// }}}
	// {{{ abstract public function resolvePage()

	abstract public function resolvePage($app, $source);

	// }}}
	// {{{ protected function instantiatePage()

	public function instantiatePage($class, $params)
	{
		if (!class_exists($class)) {
			$class_file = null;

			foreach ($this->class_map as $package_prefix => $path) {
				if (strncmp($class, $package_prefix, strlen($package_prefix)) == 0) {
					$class_file = "{$path}/{$class}.php";
					break;
				}
			}

			if ($class_file === null)
				$class_file = sprintf('%s/%s.php', $this->page_class_path, $class);

			require_once $class_file;
		}

		$page = call_user_func_array(
			array(new ReflectionClass($class), 'newInstance'), $params);

		return $page;
	}

	// }}}
	// {{{ protected function getPageMap()

	protected function getPageMap()
	{
		return array();
	}

	// }}}
	// {{{ protected function resolveLayout()

	protected function resolveLayout($app, $source)
	{
		return new SiteLayout($app);
	}

	// }}}
	// {{{ private function __construct()

	/**
	 * Creates a SitePageFactory object
	 *
	 * The constructor is private as this class uses the singleton pattern.
	 */
	private function __construct()
	{
	}

	// }}}
}

?>
