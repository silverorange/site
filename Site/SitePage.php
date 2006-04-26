<?php

require_once 'Site/SiteObject.php';
require_once 'Site/SiteLayout.php';

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
	 * Layout object to use to display this page
	 *
	 * @var SiteLayout
	 */
	public $layout = null;

	/**
	 * Application object
	 * 
	 * A reference to the {@link SiteApplication} object that created
	 * this page.
	 *
	 * @var SiteApplication
	 */
	public $app = null;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
		$this->layout = $this->createLayout();
	}

	// }}}
	// {{{ public function init()

	public function init()
	{

	}

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout('../layouts/default.php');
	}

	// }}}
}
?>
