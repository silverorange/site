<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Timer module
 *
 * This module can track execution time.
 * TODO: add labeled checkpoints
 * TODO: add a display() method to display checkpoint times and total time
 *
 * @package   Site
 * @copyright 2004-2006
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteTimerModule extends SiteApplicationModule
{
	// {{{ private properties

	/**
	 * The execution start time of this application
	 *
	 * @var double 
	 */
	private $start_time = null;

	// }}}
    // {{{ public function init()

	/**
	 * Initializes this timer module
	 */
	public function init()
	{
		$this->reset();
	}

    // }}}
	// {{{ protected function reset()

	protected function reset()
	{
		$this->start_time = microtime(true);
	}

	// }}}
	// {{{ public function getTime()

	/**
	 * Gets the current execution time of this application in milliseconds
	 *
	 * @return double the current execution time of this application in
	 *                 milliseconds.
	 */
	public function getTime()
	{
		return microtime(true) - $this->start_time;
	}

	// }}}
}

?>
