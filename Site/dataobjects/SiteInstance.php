<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteInstanceConfigSettingWrapper.php';

/**
 * A dataobject class for site instances
 *
 * @package   Site
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteMultipleInstanceModule
 */
class SiteInstance extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The shortname of this instance
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * The title of this instance
	 *
	 * @var string
	 */
	public $title;

	// }}}
	// {{{ public function loadFromShortname()

	/**
	 * Loads a instance by its shortname
	 *
	 * @param string $shortname the shortname of the instance to load.
	 *
	 * @return boolean true if the instance was loaded successfully and false
	 *                  if it was not.
	 *
	 * @deprecated Use {@link SiteInstance::loadByShortname()} instead.
	 */
	public function loadFromShortname($shortname)
	{
		return $this->loadByShortname($shortname);
	}

	// }}}
	// {{{ public function loadByShortname()

	/**
	 * Loads a instance by its shortname
	 *
	 * @param string $shortname the shortname of the instance to load.
	 *
	 * @return boolean true if the instance was loaded successfully and false
	 *                  if it was not.
	 */
	public function loadByShortname($shortname)
	{
		$row = null;

		if ($this->table !== null) {
			$sql = sprintf('select * from %s where shortname = %s',
				$this->table,
				$this->db->quote($shortname, 'text'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row === null)
			return false;

		$this->initFromRow($row);
		$this->generatePropertyHashes();
		return true;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Instance';
		$this->id_field = 'integer:id';
	}

	// }}}

	// loader methods
	// {{{ protected function loadConfigSettings()

	/**
	 * Loads the config settings for this instance
	 *
	 * @return SiteInstanceConfigSettingWrapper a recordset of config settings
	 */
	protected function loadConfigSettings()
	{
		$sql = 'select * from InstanceConfigSetting where instance = %s';
		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

		$wrapper  = SwatDBClassMap::get('SiteInstanceConfigSettingWrapper');
		$settings = SwatDB::query($this->db, $sql, $wrapper);

		$non_default = array();

		// Find all config settings that have non-default values
		foreach ($settings as $setting) {
			if (!$setting->is_default) {
				$non_default[] = $setting->name;
			}
		}

		// Remove all the config settings that have non-default replacements
		foreach ($settings as $setting) {
			if (in_array($setting->name, $non_default) &&
				$setting->is_default) {
				$settings->remove($setting);
			}
		}

		return $settings;
	}

	// }}}
}

?>
