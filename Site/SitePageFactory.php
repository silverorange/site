<?php

require_once 'Site/SiteWebApplication.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/layouts/SiteLayout.php';
require_once 'Site/exceptions/SiteClassNotFoundException.php';
require_once 'Site/exceptions/SiteNotFoundException.php';

/**
 * Resolves and creates article pages in a web application
 *
 * @package   Site
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SitePageFactory
{
	// {{{ protected properties

	/**
	 * @var SiteWebApplication
	 *
	 * @see SitePageFactory::__construct()
	 */
	protected $app;

	/**
	 * An array that maps other package classes to directories
	 *
	 * The array is of the form:
	 *
	 * <code>
	 * array(
	 *    $package_prefix => $path,
	 * );
	 * </code>
	 *
	 * Where the <code>$package_prefix</code> is the classname prefix used for
	 * the package and <code>$path</code> is the relative path where the page
	 * class source files for the package are located.
	 *
	 * By default, the prefix 'Site' is mapped to 'Site/pages'.
	 *
	 * @var array
	 */
	protected $page_class_map = array('Site' => 'Site/pages');

	/**
	 * A fallback directory in which to look for page class files
	 *
	 * This directory is checked if the class name does not match any entries
	 * in this factory's {@link SitePageFactory::$page_class_map}. This is
	 * usually used to load site-specific page classes.
	 *
	 * @var string
	 */
	protected $page_class_path = '../include/pages';

	/**
	 * The name of the default class to use if no class is provided when
	 * instantiating pages
	 *
	 * This must be either {@link SitePage} or a subclass of
	 * <code>SitePage</code>.
	 *
	 * @var string
	 */
	protected $default_page_class = 'SitePage';

	// }}}
	// {{{ public function __construct()

	/**
	 * @param SiteWebApplication $app
	 */
	public function __construct(SiteWebApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ abstract public function get()

	/**
	 * Resolves a page object from a source string
	 *
	 * @param string $source the source string for which to get the page.
	 * @param SiteLayout $layout optional. The layout to use for the page.
	 *
	 * @return SiteAbstractPage the page for the given source string.
	 */
	abstract public function get($source, SiteLayout $layout = null);

	// }}}
	// {{{ protected function getPage()

	/**
	 * Creates and returns a page object for the specified page class
	 *
	 * @param string $class the name of the page class to resolve. This must be
	 *                       either {@link SitePage} or a subclass of
	 *                       <code>SitePage</code>.
	 * @param array $arguments optional. An array of parameters to pass as
	 *                          arguments to the page constructor.
	 *
	 * @return SitePage the instantiated page object. The returned page should
	 *                   be undecorated.
	 *
	 * @throws SiteClassNotFoundException if the given class could not be
	 *                                     resolved or if the given class is
	 *                                     neither {@link SitePage} nor a
	 *                                     subclass of <code>SitePage</code>.
	 * @throws SiteNotFoundException if no file could be resolved for the given
	 *                                class and the given class is undefined.
	 */
	protected function getPage($class, SiteLayout $layout,
		array $arguments = array())
	{
		if (!$this->isPage($class)) {
			throw new SiteClassNotFoundException(sprintf('The provided page '.
				'class ‘%s’ is not a SitePage.', $class), 0, $class);
		}

		return new $class($this->app, $layout, $arguments);
	}

	// }}}
	// {{{ protected function getLayout()

	/**
	 * Resolves the layout object to use for the instantiated page
	 *
	 * By default, the layout is resolved to {@link SiteLayout}.
	 *
	 * @param string $source the source string for which to get the layout.
	 *
	 * @return SiteLayout
	 */
	protected function getLayout($source)
	{
		return new SiteLayout($this->app);
	}

	// }}}
	// {{{ protected function decorate()

	/**
	 * Creates and returns a page decorator for the specified decorator class
	 *
	 * @return SiteAbstractPage $page the page being decorated.
	 * @param string $class the name of the page decorator class to resolve.
	 *                       This must be a {@link SitePageDecorator} subclass.
	 *
	 * @return SiteDecoratedPage the decorated page.
	 *
	 * @throws SiteClassNotFoundException if the given class could not be
	 *                                     resolved or if the given class is
	 *                                     not a {@link SitePageDecorator}.
	 * @throws SiteNotFoundException if no file could be resolved for the given
	 *                                class and the given class is undefined.
	 */
	protected function decorate(SiteAbstractPage $page, $class)
	{
		if (!$this->isDecorator($class)) {
			throw new SiteClassNotFoundException(sprintf('The provided page '.
				'decorator class ‘%s’ is not a SitePageDecorator.', $class),
				0, $class);
		}

		return new $class($page);
	}

	// }}}
	// {{{ protected function loadPageClass()

	/**
	 * This method handles automagically requiring the correct class definition
	 * file according the {@link SitePageFactory::$page_class_map} and
	 * {@link SitePageFactory::$page_class_path}.
	 *
	 * @param string $class the name of the page class.
	 *
	 * @throws SiteNotFouncException if no file is found for the specified
	 *                               class.
	 * @throws SiteClassNotFoundException if no class definition is found for
	 *                                    the specified class.
	 */
	protected function loadPageClass($class)
	{
		if (!class_exists($class)) {
			$class_file = $this->page_class_path.'/'.$class.'.php';

			if (!file_exists($class_file)) {
				$class_file = null;

				// look for class file in class map
				foreach ($this->page_class_map as $package_prefix => $path) {
					$length = strlen($package_prefix);
					if (strncmp($class, $package_prefix, $length) === 0) {
						$class_file = "{$path}/{$class}.php";
						break;
					}
				}
			}

			if ($class_file === null) {
				throw new SiteNotFoundException(
					sprintf('No file found for page class ‘%s’', $class));
			}

			require_once $class_file;
		}

		if (!class_exists($class)) {
			throw new SiteClassNotFoundException(
				sprintf('No class definition found for class ‘%s’', $class),
				0, $class);
		}
	}

	// }}}
	// {{{ protected function isPage()

	protected function isPage($class)
	{
		$this->loadPageClass($class);
		return ($class === 'SitePage' || is_subclass_of($class, 'SitePage'));
	}

	// }}}
	// {{{ protected function isDecorator()

	protected function isDecorator($class)
	{
		$this->loadPageClass($class);
		return (is_subclass_of($class, 'SitePageDecorator'));
	}

	// }}}
}

?>
