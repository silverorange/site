<?php

require_once 'Site/SiteObject.php';
require_once 'Site/SiteApplication.php';
require_once 'Site/layouts/SiteLayout.php';

/**
 * Base class for a page
 *
 * @package   Site
 * @copyright 2004-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SitePage extends SiteObject
{
	// {{{ public properties

	/**
	 * Application object
	 *
	 * A reference to the {@link SiteApplication} object that created
	 * this page.
	 *
	 * @var SiteApplication
	 */
	public $app = null;
	public $layout = null;

	// }}}
	// {{{ protected properties

	protected $source = null;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null)
	{
		$this->app = $app;

		if ($layout === null)
			$this->layout = $this->createLayout();
		else
			$this->layout = $layout;
	}

	// }}}
	// {{{ public function setSource()

	public function setSource($source)
	{
		$this->source = $source;
	}

	// }}}
	// {{{ public function getSource()

	public function getSource()
	{
		return $this->source;
	}

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout($this->app, 'Site/layouts/xhtml/default.php');
	}

	// }}}
	// {{{ public function init()

	/**
	 * The first page method that is run by a {@link SiteWebApplication}.
	 * Always runs before {@link SiteLayout::init()}. This method is intended
	 * to initialize objects used by the {@link SitePage::process()} and
	 * {@link SitePage::build()} methods.
	 */
	public function init()
	{
	}

	// }}}
	// {{{ public function process()

	/**
	 * Always runs after {@link SitePage::init()} and before
	 * {@link SiteLayout::process()}. This method is intended to process
	 * data entered by the user.
	 */
	public function process()
	{
	}

	// }}}
	// {{{ public function build()

	/**
	 * Always runs after {@link SitePage::process()} and before
	 * {@link SiteLayout::build()}. This method is intended to build page
	 * content and add it to the layout.
	 */
	public function build()
	{
	}

	// }}}
	// {{{ public function finalize()

	/**
	 * Always runs after {@link SitePage::build()} and before
	 * {@link SiteLayout::complete()}. This method is indented to add HTML head
	 * entries or perform other actions that should happen after the page has
	 * been built.
	 */
	public function finalize()
	{
	}

	// }}}
}

?>
