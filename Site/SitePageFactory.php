<?php

/**
 * Resolves and creates pages for a web application.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SitePageFactory
{
    /**
     * @see SitePageFactory::__construct()
     */
    protected SiteWebApplication $app;

    /**
     * An array that maps package page classes to directories.
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
     * @var array<string, string>
     */
    protected array $page_class_map = ['Site' => 'Site/pages'];

    /**
     * Location in which to look for page class files.
     *
     * An attempt to load undefined classes from this directory is made before
     * checking the class map. This is usually used to load site-specific page
     * classes.
     */
    protected string $page_class_path = '../include/pages';

    /**
     * The name of the default class to use if no class is provided when
     * instantiating pages.
     *
     * This must be either {@link SitePage} or a subclass of
     * <code>SitePage</code>.
     *
     * @var class-string<SitePage>
     */
    protected string $default_page_class = SitePage::class;

    /**
     * An array that maps package layout classes to directories.
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
     * @var array<string, string>
     */
    protected array $layout_class_map = ['Site' => 'Site/layouts'];

    /**
     * Location in which to look for layout class files.
     *
     * An attempt to load undefined classes from this directory is made before
     * checking the class map. This is usually used to load site-specific layout
     * classes.
     */
    protected string $layout_class_path = '../include/layouts';

    /**
     * The name of the default class to use if no class is provided when
     * instantiating layouts.
     *
     * This must be either {@link SiteLayout} or a subclass of
     * <code>SiteLayout</code>.
     *
     * @var class-string<SiteLayout>
     */
    protected string $default_layout_class = SiteLayout::class;

    public function __construct(SiteWebApplication $app)
    {
        $this->app = $app;
    }

    /**
     * Resolves a page object from a source string.
     *
     * @param string      $source the source string for which to get the page
     * @param ?SiteLayout $layout optional. The layout to use for the page.
     *
     * @return SiteAbstractPage the page for the given source string
     *
     * @throws SiteNotFoundException if no suitable page is found for the
     *                               given <kbd>$source</kbd>
     */
    abstract public function resolvePage(string $source, ?SiteLayout $layout = null): SiteAbstractPage;

    /**
     * Resolves the layout object to use for the instantiated page.
     *
     * By default, the layout is resolved to {@link SiteLayout}.
     *
     * @param string $source the source string for which to get the layout
     *
     * @throws SiteClassNotFoundException
     */
    protected function resolveLayout(string $source): SiteLayout
    {
        return $this->instantiateLayout($this->default_layout_class);
    }

    /**
     * Instantiates and returns a page object for the specified page class.
     *
     * @param class-string<SitePage> $class     the name of the page class. This must be either
     *                                          {@link SitePage} or a subclass of
     *                                          <code>SitePage</code>.
     * @param array                  $arguments optional. An array of parameters to pass as
     *                                          arguments to the page constructor.
     *
     * @return SitePage the instantiated page object. The returned page should
     *                  be undecorated.
     *
     * @throws SiteClassNotFoundException if the given class could not be
     *                                    resolved or if the given class is
     *                                    neither {@link SitePage} nor a
     *                                    subclass of <code>SitePage</code>
     */
    protected function instantiatePage(
        string $class,
        SiteLayout $layout,
        array $arguments = []
    ): SitePage {
        if (!$this->isPage($class)) {
            throw new SiteClassNotFoundException(sprintf('The provided page ' .
                'class ‘%s’ is not a SitePage.', $class), 0, $class);
        }

        return new $class($this->app, $layout, $arguments);
    }

    /**
     * Instantiates and returns a layout object for the specified layout class.
     *
     * @param class-string<SiteLayout> $class    the name of the layout class. This must be either
     *                                           {@link SiteLayout} or a subclass of
     *                                           <code>SiteLayout</code>.
     * @param ?string                  $filename optional. The filename of the XHTML template
     *                                           to use for the given layout.
     *
     * @return SiteLayout the instantiated layout object
     *
     * @throws SiteClassNotFoundException if the given class could not be
     *                                    resolved or if the given class is
     *                                    neither {@link SiteLayout} nor a
     *                                    subclass of <code>SiteLayout</code>
     */
    protected function instantiateLayout(string $class, ?string $filename = null): SiteLayout
    {
        if (!$this->isLayout($class)) {
            throw new SiteClassNotFoundException(sprintf('The provided layout ' .
                'class ‘%s’ is not a SiteLayout.', $class), 0, $class);
        }

        return new $class($this->app, $filename);
    }

    /**
     * This method handles automagically requiring the correct class definition
     * file according the {@link SitePageFactory::$page_class_map} and
     * {@link SitePageFactory::$page_class_path}.
     *
     * @param class-string $class the name of the page class
     *
     * @throws SiteClassNotFoundException if no class definition is found for
     *                                    the specified class
     */
    protected function loadPageClass(string $class): void
    {
        $this->loadClass(
            $class,
            $this->page_class_path,
            $this->page_class_map
        );
    }

    /**
     * This method handles automagically requiring the correct class definition
     * file according the {@link SitePageFactory::$layout_class_map} and
     * {@link SitePageFactory::$layout_class_path}.
     *
     * @param class-string $class the name of the layout class
     *
     * @throws SiteClassNotFoundException if no class definition is found for
     *                                    the specified class
     */
    protected function loadLayoutClass(string $class): void
    {
        $this->loadClass(
            $class,
            $this->layout_class_path,
            $this->layout_class_map
        );
    }

    /**
     * This method handles automagically requiring a class definition file.
     *
     * @param class-string $class      the name of the class
     * @param string       $class_path the location in which to look for class
     *                                 definitions
     * @param array        $class_map  mapping of package prefixes to class definition
     *                                 locations used if the class definition is not
     *                                 found in <i>$class_path</i>
     *
     * @throws SiteClassNotFoundException if no class definition is found for
     *                                    the specified class
     */
    protected function loadClass(string $class, string $class_path, array $class_map): void
    {
        if (!class_exists($class)) {
            throw new SiteClassNotFoundException(
                sprintf('No class definition found for class ‘%s’', $class),
                0,
                $class
            );
        }
    }

    /**
     * @param class-string $class
     *
     * @throws SiteClassNotFoundException
     */
    protected function isPage(string $class): bool
    {
        $this->loadPageClass($class);

        return is_a($class, SitePage::class, allow_string: true);
    }

    /**
     * @param class-string $class
     *
     * @throws SiteClassNotFoundException
     */
    protected function isDecorator(string $class): bool
    {
        $this->loadPageClass($class);

        return is_subclass_of($class, SitePageDecorator::class);
    }

    /**
     * @param class-string $class
     *
     * @throws SiteClassNotFoundException
     */
    protected function isLayout(string $class): bool
    {
        $this->loadLayoutClass($class);

        return is_a($class, SiteLayout::class, allow_string: true);
    }

    /**
     * Creates and returns a page decorator for the specified decorator class.
     *
     * @param SiteAbstractPage                $page  the page being decorated
     * @param class-string<SitePageDecorator> $class the name of the page decorator class to resolve.
     *                                               This must be a {@link SitePageDecorator} subclass.
     *
     * @return SitePageDecorator the decorated page
     *
     * @throws SiteClassNotFoundException if the given class could not be
     *                                    resolved or if the given class is
     *                                    not a {@link SitePageDecorator}
     */
    protected function decorate(SiteAbstractPage $page, string $class): SitePageDecorator
    {
        if (!$this->isDecorator($class)) {
            throw new SiteClassNotFoundException(
                sprintf('The provided page ' .
                'decorator class ‘%s’ is not a SitePageDecorator.', $class),
                0,
                $class
            );
        }

        return new $class($page);
    }
}
