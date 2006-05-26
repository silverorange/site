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

	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
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
