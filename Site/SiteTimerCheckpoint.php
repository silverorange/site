<?php

require_once 'Swat/SwatObject.php';

/**
 * A timer checkpoint set by {@link SiteTimerModule}
 *
 * @package   Site
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteTimerCheckpoint extends SwatObject
{
	// {{{ private properties

	/**
	 * The time when this checkpoint was created in milliseconds
	 *
	 * @var double 
	 */
	private $time;

	/**
	 * The name of this checkpoint
	 *
	 * @var string
	 */
	private $name;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new timer checkpoint
	 *
	 * @param string $name the name of this checkpoint.
	 * @param double $time the current time in milliseconds.
	 */
	public function __construct($name, $time)
	{
		$this->name = $name;
		$this->time = $time;
	}

	// }}}
	// {{{ public function getTime()

	/**
	 * Gets the time when this checkpoint was created
	 *
	 * @return double the time when this checkpoint was created.
	 */
	public function getTime()
	{
		return $this->time;
	}

	// }}}
	// {{{ public function getName()

	/**
	 * Gets the name of this checkpoint
	 *
	 * return string the name of this checkpoint.
	 */
	public function getName()
	{
		return $this->name;
	}

	// }}}
}

?>
