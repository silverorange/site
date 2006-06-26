<?php

require_once 'Site/layouts/SiteLayout.php';

/**
 * @package   Site
 * @copyright 2006 silverorange
 */
abstract class SitePageFactory
{
	// {{{ protected properties

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
		$class_file = sprintf('%s/%s.php', $this->page_class_path, $class);
		require_once $class_file;

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
