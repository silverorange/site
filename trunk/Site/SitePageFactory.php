<?php

require_once 'Site/SiteWebApplication.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/layouts/SiteLayout.php';
require_once 'Site/exceptions/SiteClassNotFoundException.php';
require_once 'Site/exceptions/SiteNotFoundException.php';

/**
 * Resolves and creates pages for a web application
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
	 * An array that maps package page classes to directories
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
	 * Location in which to look for page class files
	 *
	 * An attempt to load undefined classes from this directory is made before
	 * checking the class map. This is usually used to load site-specific page
	 * classes.
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

	/**
	 * An array that maps package layout classes to directories
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
	 * the package and <code>$path</code> is the relative path where the layout
	 * class source files for the package are located.
	 *
	 * By default, the prefix 'Site' is mapped to 'Site/layouts'.
	 *
	 * @var array
	 */
	protected $layout_class_map = array('Site' => 'Site/layouts');

	/**
	 * Location in which to look for layout class files
	 *
	 * An attempt to load undefined classes from this directory is made before
	 * checking the class map. This is usually used to load site-specific layout
	 * classes.
	 *
	 * @var string
	 */
	protected $layout_class_path = '../include/layouts';

	/**
	 * The name of the default class to use if no class is provided when
	 * instantiating layouts
	 *
	 * This must be either {@link SiteLayout} or a subclass of
	 * <code>SiteLayout</code>.
	 *
	 * @var string
	 */
	protected $default_layout_class = 'SiteLayout';

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
	// {{{ abstract public function resolvePage()

	/**
	 * Resolves a page object from a source string
	 *
	 * @param string $source the source string for which to get the page.
	 * @param SiteLayout $layout optional. The layout to use for the page.
	 *
	 * @return SiteAbstractPage the page for the given source string.
	 *
	 * @throws SiteNotFoundException if no suitable page is found for the
	 *                               given <kbd>$source</kbd>.
	 */
	abstract public function resolvePage($source, SiteLayout $layout = null);

	// }}}
	// {{{ protected function resolveLayout()

	/**
	 * Resolves the layout object to use for the instantiated page
	 *
	 * By default, the layout is resolved to {@link SiteLayout}.
	 *
	 * @param string $source the source string for which to get the layout.
	 *
	 * @return SiteLayout
	 */
	protected function resolveLayout($source)
	{
		return $this->instantiateLayout($this->default_layout_class);
	}

	// }}}
	// {{{ protected function instantiatePage()

	/**
	 * Instantiates and returns a page object for the specified page class
	 *
	 * @param string $class the name of the page class. This must be either
	 *                       {@link SitePage} or a subclass of
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
	protected function instantiatePage($class, SiteLayout $layout,
		array $arguments = array())
	{
		if (!$this->isPage($class)) {
			throw new SiteClassNotFoundException(sprintf('The provided page '.
				'class ‘%s’ is not a SitePage.', $class), 0, $class);
		}

		return new $class($this->app, $layout, $arguments);
	}

	// }}}
	// {{{ protected function instantiateLayout()

	/**
	 * Instantiates and returns a layout object for the specified layout class
	 *
	 * @param string $class the name of the layout class. This must be either
	 *                       {@link SiteLayout} or a subclass of
	 *                       <code>SiteLayout</code>.
	 * @param string $filename optional. The filename of the XHTML template
	 *                          to use for the given layout.
	 *
	 * @return SiteLayout the instantiated layout object.
	 *
	 * @throws SiteClassNotFoundException if the given class could not be
	 *                                     resolved or if the given class is
	 *                                     neither {@link SiteLayout} nor a
	 *                                     subclass of <code>SiteLayout</code>.
	 * @throws SiteNotFoundException if no file could be resolved for the given
	 *                                class and the given class is undefined.
	 */
	protected function instantiateLayout($class, $filename = null)
	{
		if (!$this->isLayout($class)) {
			throw new SiteClassNotFoundException(sprintf('The provided layout '.
				'class ‘%s’ is not a SiteLayout.', $class), 0, $class);
		}

		return new $class($this->app, $filename);
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
	 * @throws SiteNotFoundException if no file is found for the specified
	 *                               class.
	 * @throws SiteClassNotFoundException if no class definition is found for
	 *                                    the specified class.
	 */
	protected function loadPageClass($class)
	{
		$this->loadClass($class, $this->page_class_path,
			$this->page_class_map);
	}

	// }}}
	// {{{ protected function loadLayoutClass()

	/**
	 * This method handles automagically requiring the correct class definition
	 * file according the {@link SitePageFactory::$layout_class_map} and
	 * {@link SitePageFactory::$layout_class_path}.
	 *
	 * @param string $class the name of the layout class.
	 *
	 * @throws SiteNotFoundException if no file is found for the specified
	 *                               class.
	 * @throws SiteClassNotFoundException if no class definition is found for
	 *                                    the specified class.
	 */
	protected function loadLayoutClass($class)
	{
		$this->loadClass($class, $this->layout_class_path,
			$this->layout_class_map);
	}

	// }}}
	// {{{ protected function loadClass()

	/**
	 * This method handles automagically requiring a class definition file
	 *
	 * @param string $class the name of the class.
	 * @param string $class_path the location in which to look for class
	 *                            definitions.
	 * @param array $class_map mapping of package prefixes to class definition
	 *                          locations used if the class definition is not
	 *                          found in <i>$class_path</i>.
	 *
	 * @throws SiteNotFoundException if no file is found for the specified
	 *                               class.
	 * @throws SiteClassNotFoundException if no class definition is found for
	 *                                    the specified class.
	 */
	protected function loadClass($class, $class_path, array $class_map)
	{
		if (!class_exists($class)) {
			$class_file = $class_path.'/'.$class.'.php';

			if (!file_exists($class_file)) {
				$class_file = null;

				// look for class file in class map
				foreach ($class_map as $package_prefix => $path) {
					$length = strlen($package_prefix);
					if (strncmp($class, $package_prefix, $length) === 0) {
						$class_file = "{$path}/{$class}.php";
						break;
					}
				}
			}

			if ($class_file === null) {
				throw new SiteNotFoundException(
					sprintf('No file found for class ‘%s’', $class));
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
	// {{{ protected function isLayout()

	protected function isLayout($class)
	{
		$this->loadLayoutClass($class);
		return ($class === 'SiteLayout' ||
			is_subclass_of($class, 'SiteLayout'));
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
}

?>
