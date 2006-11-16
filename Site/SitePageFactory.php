<?php

require_once 'Site/SiteWebApplication.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/layouts/SiteLayout.php';
require_once 'Site/exceptions/SiteClassNotFoundException.php';
require_once 'Site/exceptions/SiteNotFoundException.php';

/**
 * Resolves and creates article pages in a web application
 *
 * Page factories implement the singleton pattern. Create a new page factory
 * using the {@link SitePageFactory::instance()} method.
 *
 * @package   Site
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
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
	protected $class_map = array('Site' => 'Site/pages');

	/**
	 * A fallback directory in which to look for page class files
	 *
	 * This directory is checked if the class name does not match any entries
	 * in this factory's class map. This is usually used to load site-specific
	 * page classes.
	 *
	 * @var string
	 */
	protected $page_class_path = '../include/pages';

	// }}}
	// {{{ abstract public static function instance()

	/**
	 * Gets the singleton instance of this factory object
	 *
	 * @return SitePageFactory the singleton instance.
	 */
	abstract public static function instance();

	// }}}
	// {{{ abstract public function resolvePage()

	/**
	 * Resolves a page object from a source string
	 *
	 * @param SiteWebApplication $app the web application for which the page is
	 *                                 being resolved.
	 * @param string $source the source string for which to get the page.
	 *
	 * @return SitePage the page for the given source string.
	 */
	abstract public function resolvePage(SiteWebApplication $app, $source);

	// }}}
	// {{{ protected function instantiatePage()

	/**
	 * Instantiates a page object
	 *
	 * @param string $class the name of the page class to resolve. This must be
	 *                       either {@link SitePage} or a subclass of
	 *                       SitePage.
	 * @param array $params an array of parameters to pass the the constructor
	 *                       of the page object.
	 *
	 * @return SitePage the instantiated page object.
	 *
	 * @throws SiteClassNotFoundException if the given class could not be
	 *                                     resolved or if the given class is
	 *                                     neither SitePage nor a subclass of
	 *                                     SitePage.
	 * @throws SiteNotFoundException if no file could be resolved for the given
	 *                                class and the given class is undefined.
	 */
	public function instantiatePage($class, $params)
	{
		if (!class_exists($class)) {
			$class_file = "{$this->page_class_path}/{$class}.php";

			if (!file_exists($class_file)) {
				$class_file = null;

				// look for class file in class map
				foreach ($this->class_map as $package_prefix => $path) {
					if (strncmp($class, $package_prefix, strlen($package_prefix)) == 0) {
						$class_file = "{$path}/{$class}.php";
						break;
					}
				}
			}

			if ($class_file === null)
				throw new SiteNotFoundException(sprintf('No file found '.
					' for class ‘%s’', $class));

			require_once $class_file;
		}

		if (!class_exists($class))
			throw new SiteClassNotFoundException(sprintf('No class definition '.
				'found for ‘%s’', $class), 0, $class);

		if (strcmp($class, 'SitePage') != 0 &&
			!is_subclass_of($class, 'SitePage'))
			throw new SiteClassNotFoundException(sprintf('The provided class '.
				'parameter ‘%s’ is not a SitePage.', $class), 0, $class);

		$page = call_user_func_array(
			array(new ReflectionClass($class), 'newInstance'), $params);

		return $page;
	}

	// }}}
	// {{{ protected function getPageMap()

	/**
	 * Gets an array of page mappings used to resolve pages
	 *
	 * The page mappings are an array of the form:
	 *
	 *   source expression => page class
	 *
	 * The <i>source expression</i> is an regular expression using PREG syntax
	 * sans-delimiters. The <i>page class</i> is the class name of the page to
	 * be resolved.
	 *
	 * For example, the following mapping array will match the source
	 * 'about/content' to the class 'ContactPage':
	 *
	 * <code>
	 * array('^(about/contact)$' => 'ContactPage');
	 * </code>
	 *
	 * By default, no page mappings are defined. Subclasses may define
	 * additional mappings by extending this method.
	 *
	 * @return array the page mappings of this factory.
	 */
	protected function getPageMap()
	{
		return array();
	}

	// }}}
	// {{{ protected function resolveLayout()

	/**
	 * Resolves the layout object to use for the instantiated page
	 *
	 * By default, the layout is resolved to {@link SiteLayout}.
	 *
	 * @param SiteWebApplication $app the web application for which the layout
	 *                                 is being resolved.
	 * @param string $source the source string for which to get the layout.
	 *
	 * @return SiteLayout
	 */
	protected function resolveLayout(SiteWebApplication $app, $source)
	{
		return new SiteLayout($app);
	}

	// }}}
	// {{{ protected function __construct()

	/**
	 * Creates a SitePageFactory object
	 *
	 * The constructor is protected as this class uses the singleton pattern.
	 */
	protected function __construct()
	{
	}

	// }}}
}

?>
