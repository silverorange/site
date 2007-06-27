<?php

require_once 'Site/SiteObject.php';

/**
 * A parameter for a command line argument
 *
 * @package   Site
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteCommandLineArgument::addParameter()
 */
class SiteCommandLineArgumentParameter extends SiteObject
{
	// {{{ protected properties

	/**
	 * @var string
	 *
	 * @see SiteCommandLineArgumentParameter::getErrorMessage()
	 */
	protected $error_message;

	/**
	 * The type of this parameter 
	 *
	 * Type must be one of 'int', 'integer', 'double', 'float', or 'string'.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * The default value of this parameter
	 *
	 * @var mixed
	 */
	protected $default;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new command line argument parameter
	 *
	 * @param string $type the type of the parameter. Must be one of 'int',
	 *                      'integer', 'double', 'float', or 'string'.
	 * @param string $error_message a message displayed when the user omits a
	 *                               required parameter or the user specifies
	 *                               a parameter value of the wrong type.
	 * @param mixed $default optional default value. If a default value is
	 *                        specified and the user does not specify the
	 *                        parameter value, the default value is used.
	 */
	public function __construct($type, $error_message, $default = null)
	{
		$this->type = $type;
		$this->error_message = $error_message;
		$this->default = $default;
	}

	// }}}
	// {{{ public function getErrorMessage()

	/**
	 * Gets the error message text of this parameter 
	 *
	 * @return string a message displayed when the user omits a this required
	 *                 parameter or when the user specifies value for this
	 *                 parameter of the wrong type.
	 */
	public function getErrorMessage()
	{
		return $this->error_message;
	}

	// }}}
	// {{{ public function hasDefault()

	/**
	 * Whether or not a default value is specified for this parameter
	 *
	 * @return boolean true if a default value is specified for this parameter
	 *                  and false if it is not.
	 */
	public function hasDefault()
	{
		return ($this->default !== null);
	}

	// }}}
	// {{{ public function getDefault()

	/**
	 * Gets the default value of this parameter
	 *
	 * @return mixed the default value of this parameter.
	 */
	public function getDefault()
	{
		return $this->default;
	}

	// }}}
	// {{{ public function validate()

	/**
	 * Checks a value to see if it is the correct type for this parameter
	 *
	 * @param mixed $value the value to check.
	 *
	 * @return boolean true if the value is of the correct type for this
	 *                  parameter and false if it is not.
	 */
	public function validate($value)
	{
		$valid = false;

		switch ($this->type) {
		case 'integer':
		case 'int':
			if (is_numeric($value) && strcmp((int)$value, $value) == 0)
				$valid = true;

			break;
		case 'double':
		case 'float':
			if (is_numeric($value))
				$valid = true;

			break;
		case 'string':
		default:
			$valid = true;
			break;
		}

		return $valid;
	}

	// }}}
}

?>
