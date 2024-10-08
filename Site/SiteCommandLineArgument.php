<?php

/**
 * A command line argument for a command line application.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SiteCommandLineApplication
 */
class SiteCommandLineArgument extends SiteObject
{
    /**
     * Text explaining how to use this argument.
     *
     * @var string
     */
    protected $documentation;

    /**
     * Names of this argument.
     *
     * Argument names are what users enter on the command line. For example:
     * '--foo'.
     *
     * @var array
     */
    protected $names = [];

    /**
     * The name of the method this argument calls on its application.
     *
     * @var string
     */
    protected $method = '';

    /**
     * Additional parameters of this command line argument.
     *
     * @var array
     *
     * @see SiteCommandLineArgument::addParameter()
     */
    protected $parameters = [];

    /**
     * Creates a new command line argument parameter.
     *
     * @param array  $names         a list of names for this command line argument.
     *                              Names are what the user enters on the command line.
     * @param string $method        the name of the method this argument calls on its
     *                              application
     * @param string $documentation text explaining how to use this argument
     */
    public function __construct($names, $method, $documentation)
    {
        $this->names = $names;
        $this->method = $method;
        $this->documentation = $documentation;
    }

    /**
     * Gets the argument names of this command line argument.
     *
     * Argument names are what users enter on the command line. For example:
     * '--foo'.
     *
     * @return array an array of argument names of this command line argument
     */
    public function &getNames()
    {
        return $this->names;
    }

    /**
     * Gets all additional parameters of this command line argument.
     *
     * @return array an array of SiteCommandLineArgumentParameter objects
     */
    public function &getParameters()
    {
        return $this->parameters;
    }

    /**
     * Whether or not this argument has one or more additional parameters.
     *
     * @return bool true if this argument has one or more additional
     *              parameters and false if it does not
     */
    public function hasParameter()
    {
        return count($this->parameters) > 0;
    }

    /**
     * Adds an additional parameter to this command line argument.
     *
     * This method is useful if you have a complex command line argument. For
     * example, if you want to allow verbosity level to be specified as
     * '--verbosity 3' you need to add an integer parameter to the verbosity
     * argument.
     *
     * @param string $type          the type of the parameter. Must be one of 'int',
     *                              'integer', 'double', 'float', or 'string'.
     * @param string $error_message a message displayed when the user omits a
     *                              required parameter or when the user
     *                              specified a parameter value of the wrong
     *                              type
     * @param mixed  $default       optional default value. If a default value is
     *                              specified and the user does not specify the
     *                              parameter value, the default value is used.
     */
    public function addParameter($type, $error_message, $default = null)
    {
        $this->parameters[] = new SiteCommandLineArgumentParameter(
            $type,
            $error_message,
            $default
        );
    }

    /**
     * Gets the name of the method this argument calls on its application.
     *
     * @return string the name of the method this argument calls on its
     *                application
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Gets text explaining how to use this argument.
     *
     * @return string text explaining how to use this argument
     */
    public function getDocumentation()
    {
        return $this->documentation;
    }
}
