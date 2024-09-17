<?php

/**
 * Base class for a layout.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteLayout extends SiteObject
{
    /**
     * @var SiteWebApplication
     */
    public $app;

    /**
     * @var SiteLayoutData
     */
    public $data;

    /**
     * @var SwatHtmlHeadEntrySet
     */
    protected $html_head_entries;

    /**
     * @var array
     *
     * @see SiteLayout::addBodyClass()
     * @see SiteLayout::removeBodyClass()
     */
    protected $body_classes = [];

    private $template_class;
    private $current_capture;
    private $capture_prepend = false;

    public function __construct(SiteApplication $app, $template_class = null)
    {
        $this->app = $app;
        $this->html_head_entries = new SwatHtmlHeadEntrySet();

        if ($template_class === null) {
            $template_class = SiteDefaultTemplate::class;
        }

        $this->template_class = $template_class;
        $this->data = new SiteLayoutData();
    }

    public function setTemplateClass($template_class)
    {
        $this->template_class = $template_class;
    }

    public function display()
    {
        $this->data->display($this->template_class);
    }

    public function startCapture($name, $prepend = false)
    {
        if ($this->current_capture !== null) {
            throw new SiteException('Capture already in progress.');
        }

        $this->current_capture = $name;
        $this->capture_prepend = $prepend;
        ob_start();
    }

    public function endCapture()
    {
        if ($this->current_capture === null) {
            throw new SiteException('No capture was started.');
        }

        $name = $this->current_capture;

        if (isset($this->data->{$name})) {
            if ($this->capture_prepend) {
                $this->data->{$name} = ob_get_clean() . $this->data->{$name};
            } else {
                $this->data->{$name} .= ob_get_clean();
            }
        } else {
            $this->data->{$name} = ob_get_clean();
        }

        $this->current_capture = null;
    }

    public function clear($name)
    {
        if (!isset($this->data->{$name})) {
            throw new SiteException("Layout data property '{$name}' does not " .
                'exist and cannot be cleared.');
        }

        $this->data->{$name} = '';
    }

    // init phase

    public function init()
    {
        $this->data->basehref = $this->app->getBaseHref();
        $this->data->title = '';
        $this->data->html_title = '';
        $this->data->site_title =
                SwatString::minimizeEntities($this->app->config->site->title);

        if (isset($this->app->config->site->meta_description)) {
            $this->data->meta_description =
                SwatString::minimizeEntities(
                    $this->app->config->site->meta_description
                );
        } else {
            $this->data->meta_description = '';
        }

        $this->data->analytics = '';
        $this->data->meta_keywords = '';
        $this->data->extra_headers = '';
        $this->data->extra_footers = '';
        $this->data->mobile_meta_tags = '';

        if (isset($this->app->mobile) && $this->app->mobile->isMobileUrl()) {
            $this->addBodyClass('mobile');

            ob_start();
            $this->app->mobile->displayMobileMetaTags();
            $this->data->mobile_meta_tags = ob_get_clean();
        }
    }

    // process phase

    public function process() {}

    // build phase

    public function build() {}

    // finalize phase

    public function finalize() {}

    /**
     * @param string|SwatHtmlHeadEntry $entry
     */
    public function addHtmlHeadEntry($entry)
    {
        $this->html_head_entries->addEntry($entry);
    }

    public function addHtmlHeadEntrySet(SwatHtmlHeadEntrySet $set)
    {
        $this->html_head_entries->addEntrySet($set);
    }

    /**
     * Adds a body class to this layout.
     *
     * @param array|string $class either a string or an array containing the
     *                            class names to add. If the class names
     *                            already exist in this layout, they are
     *                            ignored.
     */
    public function addBodyClass($class)
    {
        if (!is_array($class)) {
            $class = [$class];
        }

        $this->body_classes = array_unique(
            array_merge($this->body_classes, $class)
        );
    }

    /**
     * Removes a body class from this layout.
     *
     * @param array|string $class either a string or an array containing the
     *                            class names to remove. If the class names
     *                            do not exist in this layout, they are
     *                            ignored.
     */
    public function removeBodyClass($class)
    {
        if (!is_array($class)) {
            $class = [$class];
        }

        $this->body_classes = array_diff($this->body_classes, $class);
    }

    // complete phase

    public function complete()
    {
        $this->completeHtmlHeadEntries();
        $this->completeBodyClasses();
    }

    protected function completeBodyClasses()
    {
        // don't overwrite custom use of body_class data field
        if (!isset($this->data->body_classes)) {
            $this->data->body_classes = '';
            if (count($this->body_classes) > 0) {
                $this->data->body_classes = sprintf(
                    ' class="%s"',
                    SwatString::minimizeEntities(
                        implode(' ', $this->body_classes)
                    )
                );
            }
        }
    }

    protected function completeHtmlHeadEntries()
    {
        $resources = $this->app->config->resources;
        $factory = $this->getHtmlHeadEntrySetDisplayerFactory();
        $displayer = $factory->build($this->app);

        // get resource tag
        $tag = $this->getTagByFlagFile();
        if ($tag === null) {
            if ($this->app->config->resources->tag === null) {
                // support deprecated site.resource_tag config option
                $tag = $this->app->config->site->resource_tag;
            } else {
                $tag = $resources->tag;
            }
        }

        // get combine option
        $combine = ($resources->combine
            && $this->getCombineEnabledByFlagFile());

        // get minify option
        $minify = ($resources->minify
            && $this->getMinifyEnabledByFlagFile());

        $this->startCapture('html_head_entries');

        $displayer->display(
            $this->html_head_entries,
            $this->app->getBaseHref(),
            $tag,
            $combine,
            $minify
        );

        $this->endCapture();
    }

    /**
     * Gets whether or not the flag file generated during the concentrate build
     * exists.
     *
     * @return bool true if the file exists, false if it does not
     */
    protected function getCombineEnabledByFlagFile()
    {
        $www_root = dirname($_SERVER['SCRIPT_FILENAME']);
        $filename = $www_root . DIRECTORY_SEPARATOR .
            Concentrate_FlagFile::COMBINED;

        return file_exists($filename);
    }

    /**
     * Gets whether or not the flag file generated during the concentrate build
     * exists.
     *
     * @return bool true if the file exists, false if it does not
     */
    protected function getCompileEnabledByFlagFile()
    {
        $www_root = dirname($_SERVER['SCRIPT_FILENAME']);
        $filename = $www_root . DIRECTORY_SEPARATOR .
            Concentrate_FlagFile::COMPILED;

        return file_exists($filename);
    }

    /**
     * Gets whether or not the flag file generated during the concentrate build
     * exists.
     *
     * @return bool true if the file exists, false if it does not
     */
    protected function getMinifyEnabledByFlagFile()
    {
        $www_root = dirname($_SERVER['SCRIPT_FILENAME']);
        $filename = $www_root . DIRECTORY_SEPARATOR .
            Concentrate_FlagFile::MINIFIED;

        return file_exists($filename);
    }

    /**
     * Gets the resource tag from a flag file that can be generated during
     * a site build process.
     *
     * If the flag file is present, the tag value in the file overrides the
     * value in the site's configuration.
     *
     * @return string the resource tag or null if the flag file is not present
     */
    protected function getTagByFlagFile()
    {
        $tag = null;

        $www_root = dirname($_SERVER['SCRIPT_FILENAME']);
        $filename = $www_root . DIRECTORY_SEPARATOR . '.resource-tag';

        if (file_exists($filename) && is_readable($filename)) {
            $tag = trim(file_get_contents($filename));
        }

        return $tag;
    }

    protected function getHtmlHeadEntrySetDisplayerFactory()
    {
        return new SiteHtmlHeadEntrySetDisplayerFactory();
    }
}
