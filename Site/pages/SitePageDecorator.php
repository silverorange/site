<?php

/**
 * Base class for a page decorator.
 *
 * @copyright 2004-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SitePageDecorator extends SiteAbstractPage
{
    /**
     * @var SitePage
     */
    protected $page;

    public function __construct(SiteAbstractPage $page)
    {
        $this->page = $page;
        $this->app = $page->app;
        $this->layout = $page->layout;
        $this->arguments = $page->arguments;
    }

    /**
     * Sets the source string of this page.
     *
     * This is the Apache rewritten query string passed to the page factory. It
     * is the visible part of the URL after the base href and excluding
     * additional query parameters.
     *
     * Note: Ideally, the source string would be set in the constructor of
     * this class and would only have a public accessor method. A setter
     * method exists here for backwards compatibility.
     *
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
        $this->page->setSource($source);
    }

    protected function setLayout(SiteLayout $layout)
    {
        $this->layout = $layout;
        $this->page->setLayout($layout);
    }

    // init phase

    public function init()
    {
        $this->page->init();
        parent::init();
    }

    // process phase

    public function process()
    {
        $this->page->process();
        parent::process();
    }

    // build phase

    public function build()
    {
        $this->page->build();

        $this->buildTitle();
        $this->buildMetaDescription();
        $this->buildNavBar();
        $this->buildContent();

        parent::build();
    }

    protected function buildTitle() {}

    protected function buildMetaDescription() {}

    protected function buildContent() {}

    protected function buildNavBar() {}

    // finalize phase

    public function finalize()
    {
        $this->page->finalize();
        parent::finalize();
    }
}
