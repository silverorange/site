<?php

/**
 * A timer checkpoint set by {@link SiteTimerModule}.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteTimerCheckpoint extends SwatObject
{
    /**
     * The time when this checkpoint was created in milliseconds.
     *
     * @var float
     */
    private $time;

    /**
     * The name of this checkpoint.
     *
     * @var string
     */
    private $name;

    /**
     * The amount of memory used when this checkpoint was set.
     *
     * @var int
     */
    private $memory_usage;

    /**
     * Creates a new timer checkpoint.
     *
     * @param string $name         the name of this checkpoint
     * @param float  $time         the current time in milliseconds
     * @param int    $memory_usage the number of bytes of memory currently in
     *                             use
     */
    public function __construct($name, $time, $memory_usage)
    {
        $this->name = $name;
        $this->time = $time;
        $this->memory_usage = $memory_usage;
    }

    /**
     * Gets the time when this checkpoint was created.
     *
     * @return float the time when this checkpoint was created
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Gets the name of this checkpoint.
     *
     * return string the name of this checkpoint.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the memory use of this checkpoint.
     *
     * return string the memory use of this checkpoint.
     */
    public function getMemoryUsage()
    {
        return $this->memory_usage;
    }
}
