<?php

require_once 'Site/SiteObject.php';
require_once 'Site/SiteApplication.php';

/**
 * Base class for an application module
 *
 * @package   Site
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteApplicationModule extends SiteObject
{
	// {{{ private properties

	/**
	 * Reference to the application object that contains this module
	 *
	 * @var SiteApplication
	 */
	protected $app;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ abstract public function init()

	abstract public function init();

	// }}}
}
?>
