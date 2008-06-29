<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteInstance.php';
require_once 'Site/dataobjects/SiteGadgetInstanceSettingValueWrapper.php';

/**
 * A gadget that belongs to a site instance
 *
 * Responsible for binding settings in the database to a gadget.
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteGadget
 */
class SiteGadgetInstance extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Id of this gadget instance
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The gadget class of this instance
	 *
	 * This must be a valid gadget class name. See
	 * {@link SiteGadgetFactory::getAvailable() for a list of available gadget
	 * classes.
	 *
	 * @var string
	 */
	public $gadget;

	/**
	 * Position of this sidebar gadget relative to other gadgets
	 *
	 * This should be the natural ordering of gadgets when selecting multiple
	 * gadget instances from the database.
	 *
	 * @var integer
	 */
	public $displayorder = 0;

	// }}}
	// {{{ public function load()

	/**
	 * Loads this gadget instance
	 *
	 * @param integer $id the database id of this gadget instance.
	 * @param SiteInstance $instance optional. The instance to load the gadget
	 *                                instance in. If the application does not
	 *                                use instances, this should be null. If
	 *                                upsecified, the instance is not checked.
	 *
	 * @return boolean true if this gadget instance was loaded and false if it
	 *                 was not.
	 */
	public function load($id, SiteInstance $instance = null)
	{
		$this->checkDB();

		$loaded = false;
		$row = null;
		if ($this->table !== null && $this->id_field !== null) {
			$id_field = new SwatDBField($this->id_field, 'integer');

			$sql = sprintf('select * from %s where %s = %s',
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
		$this->table = 'GadgetInstance';
		$this->id_field = 'integer:id';
		$this->registerInternalProperty('instance', 'SiteInstance');
	}

	// }}}
	// {{{ protected function loadSettingValues()

	/**
	 * Loads setting values for this gadget instance
	 *
	 * @return SiteGadgetInstanceSettingValueWrapper the setting values of
	 *                                                this gadget instance.
	 */
	protected function loadSettingValues()
	{
		$sql = sprintf('select * from GadgetInstanceSettingValue
			where gadget_instance = %s',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('SiteGadgetInstanceSettingValueWrapper'));
	}

	// }}}
}

?>
