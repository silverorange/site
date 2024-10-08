<?php

/**
 * Thrown when an invalid property of an object is accessed.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteInvalidPropertyException extends SiteException
{
    /**
     * The name of the property that is invalid.
     *
     * @var string
     */
    protected $property;

    /**
     * The object the property is invalid for.
     *
     * @var mixed
     */
    protected $object;

    /**
     * Creates a new invalid property exception.
     *
     * @param string $message  the message of the exception
     * @param int    $code     the code of the exception
     * @param mixed  $object   the object the property is invalid for
     * @param string $property the name of the property that is invalid
     */
    public function __construct(
        $message = null,
        $code = 0,
        $object = null,
        $property = null
    ) {
        parent::__construct($message, $code);
        $this->object = $object;
        $this->property = $property;
    }

    /**
     * Gets the object the property is invalid for.
     *
     * @return mixed the object the property is invalid for
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Gets the name of the property that is invalid.
     *
     * @return string the name of the property that is invalid
     */
    public function getProperty()
    {
        return $this->property;
    }
}
