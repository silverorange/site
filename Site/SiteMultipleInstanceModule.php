<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for multiple instances
 *
 * @package   Site
 * @copyright 2007 silverorange
 */
class SiteMultipleInstanceModule extends SiteApplicationModule
{
	// {{{ protected properties

	/**
	 * The current instance of this site
	 *
	 * @var string
	 */
	protected $instance = 'default';

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this module
	 */
	public function init()
	{
		$instance = SiteApplication::initVar('instance');
		if ($instance !== null)
			$this->instance = $instance;
	}

	// }}}
	// {{{ public function getInstance()

	/**
	 * Gets the current instance of this site
	 *
	 * @return string the current instance of this site.
	 */
	public function getInstance()
	{
		return $this->instance;
	}

	// }}}
}

?>
