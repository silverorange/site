<?php

require_once 'Site/SiteObject.php';
require_once 'Site/layouts/SiteLayout.php';

/**
 * Base class for a page
 *
 * @package   Site
 * @copyright 2004-2006 silverorange
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

	public function init()
	{
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
	}

	// }}}
	// {{{ public function build()

	public function build()
	{
	}

	// }}}
}

?>
