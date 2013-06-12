<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatObject.php';

/**
 * Setting types and the corresponding PHP types are:
 *
 * - 'boolean' PHP type boolean
 * - 'date'    PHP type SwatDate
 * - 'float'   PHP type float
 * - 'integer' PHP type integer
 * - 'string'  PHP type string
 * - 'text'    PHP type string
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteGadgetSetting extends SwatObject
{
	// {{{ protected properties

	/**
	 * The name of this setting
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The title of this setting
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * The type of this setting
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * The default value of this setting
	 *
	 * @var mixed
	 */
	protected $default;

	/**
	 * Valid type names
	 *
	 * @var array
	 */
	protected static $valid_types = array(
		'boolean',
		'date',
		'float',
		'integer',
		'string',
		'text',
	);

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new gadget setting
	 *
	 * @param string $name the programmatic name of the setting. This should
	 *                     follow the naming rules for PHP variables.
	 * @param string $title the title of the setting. This may be used for
	 *                      display in a settings editor, for example.
	 * @param string $type optional. the type. Should be one of: 'boolean',
	 *                     'integer', 'float', 'date', 'string' or 'text'. Text
	 *                     and string are equivalent except they may be edited
	 *                     differently in a settings editor. If not specified,
	 *                     'string' is used.
	 * @param mixed $default optional. The default value of the setting. If
	 *                        not specified, null is used. This value should be
	 *                        of the PHP type corresponding to <i>$type</i>.
	 *
	 * @throws InvalidArgumentException if the specified <i>$type</i> is not a
	 *                                  valid setting type.
	 */
	public function __construct($name, $title, $type, $default = null)
	{
		if (!in_array($type, self::$valid_types)) {
			throw new InvalidArgumentException('Type "'.$type.'" is not a '.
				'valid setting type.');
		}

		$this->name    = (string)$name;
		$this->title   = (string)$title;
		$this->type    = (string)$type;
		$this->default = $default;
	}

	// }}}
	// {{{ public function getName()

	/**
	 * Gets the name of this setting
	 *
	 * @return string the name of this setting
	 */
	public function getName()
	{
		return $this->name;
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this setting
	 *
	 * @return string the title of this setting.
	 */
	public function getTitle()
	{
		return $this->title;
	}

	// }}}
	// {{{ public function getDefault()

	/**
	 * Gets the default value of this setting
	 *
	 * @return mixed the default value of this setting.
	 */
	public function getDefault()
	{
		return $this->default;
	}

	// }}}
	// {{{ public function getType()

	/**
	 * Gets the type of this setting
	 *
	 * @return string one of 'boolean', 'date', 'float', 'integer', 'string' or
	 *                'text'.
	 */
	public function getType()
	{
		return $this->type;
	}

	// }}}
}

?>
