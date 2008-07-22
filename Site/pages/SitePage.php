<?php

require_once 'Site/pages/SiteAbstractPage.php';

/**
 * Base class for a concrete page
 *
 * @package   Site
 * @copyright 2004-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SitePage extends SiteAbstractPage
{
	// {{{ public function __construct()

	/**
	 * Creates a concrete page object which may optionally be decorated
	 *
	 * Note: Ideally, the source string would be passed as the third parameter
	 * of this method. The source string is set separately using
	 * {@link SitePage::setSource} to maintain backwards compatibility.
	 *
	 * @param SiteApplication $app
	 * @param SiteLayout $layout optional.
	 * @param array $arguments optional. Additional arguments passed to this
	 *                          page. See
	 *                          {@link SiteAbstractPage::getArgument()} and
	 *                          {@link SiteAbstractPage::getArgumentMap()}.
	 */
	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		array $arguments = array())
	{
		$this->app       = $app;
		$this->layout    = ($layout === null) ? $this->createLayout() : $layout;
		$this->arguments = $arguments;
	}

	// }}}
	// {{{ public function setSource()

	/**
	 * Sets the source string of this page
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
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
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
}

?>
