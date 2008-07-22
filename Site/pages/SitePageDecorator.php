<?php

require_once 'Site/pages/SiteAbstractPage.php';

/**
 * Base class for a page decorator
 *
 * @package   Site
 * @copyright 2004-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SitePageDecorator extends SiteAbstractPage
{
	// {{{ protected properties

	/**
	 * @var SitePage
	 */
	protected $page;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteAbstractPage $page)
	{
		$this->page      = $page;
		$this->app       = $page->app;
		$this->layout    = $page->layout;
		$this->source    = $page->source;
		$this->arguments = $page->arguments;
	}

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return $this->page->createLayout();
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		$this->page->init();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		$this->page->process();
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->page->build();

		$this->buildTitle();
		$this->buildMetaDescription();
		$this->buildNavBar();
		$this->buildContent();
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
	}

	// }}}
	// {{{ protected function buildMetaDescription()

	protected function buildMetaDescription()
	{
	}

	// }}}
	// {{{ protected function buildContent()

	protected function buildContent()
	{
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		$this->page->finalize();
	}

	// }}}
}

?>
