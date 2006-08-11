<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Site/SiteApplicationModule.php';
require_once 'Site/SiteTimerCheckpoint.php';

/**
 * A module to profile web-applications
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

	/**
	 * A set of timer checkpoints used by this module
	 *
	 * @var array
	 */
	private $checkpoints = array();

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
	// {{{ public function getTime()

	/**
	 * Gets the current execution time of this application in milliseconds
	 *
	 * @return double the current execution time of this application in
	 *                 milliseconds.
	 */
	public function getTime()
	{
		return (microtime(true) * 1000) - $this->start_time;
	}

	// }}}
	// {{{ public function setCheckpoint()

	/**
	 * Sets a timer checkpoint
	 *
	 * @param string $name the name of the checkpoint
	 */
	public function setCheckpoint($name)
	{
		$this->checkpoints[] =
			new SiteTimerCheckpoint($name, $this->getTime());
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays a summary of all checkpoints and the total time of this timer
	 * module
	 */
	public function display()
	{
		echo '<dl>';

		$dt_tag = new SwatHtmlTag('dt');
		$dd_tag = new SwatHtmlTag('dd');

		// display checkpoints
		$last_time = 0;
		foreach ($this->checkpoints as $checkpoint) {
			$time = sprintf('%s ms',
				SwatString::numberFormat($checkpoint->getTime() - $last_time,
				3));

			$dt_tag->setContent($checkpoint->getName());
			$dt_tag->display();
			$dd_tag->setContent($time);
			$dd_tag->display();

			$last_time = $checkpoint->getTime();
		}

		// display total time
		$time = sprintf('%s ms',
			SwatString::numberFormat($this->getTime(), 3));

		$dt_tag->setContent('Total');
		$dt_tag->class = 'site-timer-module-total';
		$dt_tag->display();
		$dd_tag->setContent($time);
		$dd_tag->class = 'site-timer-module-total';
		$dd_tag->display();

		echo '</dl>';
	}

	// }}}
	// {{{ protected function reset()

	/**
	 * Resets this timer
	 *
	 * All checkpoints are cleared.
	 */
	protected function reset()
	{
		$this->start_time = microtime(true) * 1000;
		$this->checkpoints = array();
	}

	// }}}
}

?>
