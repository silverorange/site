<?php

require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A setting value for a particular gadget instance
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteGadgetInstance
 */
class SiteGadgetInstanceSettingValue extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The name of the setting
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * The boolean value of the setting
	 *
	 * @var boolean
	 */
	public $value_boolean;

	/**
	 * The date value of the setting
	 *
	 * @var SwatDate
	 */
	public $value_date;

	/**
	 * The float value of the setting
	 *
	 * @var float
	 */
	public $value_float;

	/**
	 * The integer value of the setting
	 *
	 * @var integer
	 */
	public $value_integer;

	/**
	 * The string value of the setting
	 *
	 * @var string
	 */
	public $value_string;

	/**
	 * The text value of the setting
	 *
	 * @var string
	 */
	public $value_text;

	// }}}
	// {{{ public function getValue()

	/**
	 * @param string $type
	 *
	 * @return mixed
	 */
	public function getValue($type)
	{
		$value = null;

		switch ($type) {
		case 'boolean':
			$value = $this->value_boolean;
			break;

		case 'date':
			$value = $this->value_date;
			break;

		case 'float':
			$value = $this->value_float;
			break;

		case 'integer':
			$value = $this->value_integer;
			break;

		case 'text':
			$value = $this->value_text;
			break;

		case 'string':
		default:
			$value = $this->value_string;
			break;
		}

		return $value;
	}

	// }}}
	// {{{ public function setValue()

	/**
	 * @param string $type
	 * @param mixed $value
	 */
	public function setValue($type, $value)
	{
		switch ($type) {
		case 'boolean':
			$this->value_boolean = (boolean)$value;
			break;

		case 'date':
			if (!($value instanceof SwatDate)) {
				$value = new SwatDate($value);
			}
			$this->value_date = $value;
			break;

		case 'float':
			$this->value_float = (float)$value;
			break;

		case 'integer':
			$this->value_integer = (integer)$value;
			break;

		case 'text':
			$this->value_text = (string)$value;
			break;

		case 'string':
		default:
			$this->value_string = (string)$value;
			break;
		}
	}

	// }}}
	// {{{ public function load()

	/**
	 * Loads this setting value
	 *
	 * @param integer $id the database id of this setting value.
	 * @param SiteInstance $instance optional. The instance to load the setting
	 *                                value in. If the application does not use
	 *                                instances, this should be null. If
	 *                                unspecified, the instance is not checked.
	 *
	 * @return boolean true if this setting value and false if it was not.
	 */
	public function load($id, SiteInstance $instance = null)
	{
		$this->checkDB();

		$loaded = false;
		$row = null;
		if ($this->table !== null && $this->id_field !== null) {
			$id_field = new SwatDBField($this->id_field, 'integer');

			$sql = sprintf('select %1$s.* from %1$s
				inner join GadgetInstance on
					%1$s.gadget_instance = GadgetInstance.id
				where %1$s.%2$s = %3$s',
				$this->table,
				$id_field->name,
				$this->db->quote($id, $id_field->type));

			$instance_id  = ($instance === null) ? null : $instance->id;
			if ($instance_id !== null) {
				$sql.=sprintf(' and instance %s %s',
					SwatDB::equalityOperator($instance_id),
					$this->db->quote($instance_id, 'integer'));
			}

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$loaded = true;
		}

		return $loaded;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'GadgetInstanceSettingValue';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty('gadget_instance',
			'SiteGadgetInstance');

		$this->registerDateProperty('value_date');
	}

	// }}}
}

?>
