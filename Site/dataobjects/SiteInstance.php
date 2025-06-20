<?php

/**
 * A dataobject class for site instances.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SiteMultipleInstanceModule
 *
 * @property int                              $id
 * @property ?string                          $shortname
 * @property ?string                          $title
 * @property SiteInstanceConfigSettingWrapper $config_settings
 */
class SiteInstance extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * The shortname of this instance.
     *
     * @var string
     */
    public $shortname;

    /**
     * The title of this instance.
     *
     * @var string
     */
    public $title;

    /**
     * Loads a instance by its shortname.
     *
     * @param string $shortname the shortname of the instance to load
     *
     * @return bool true if the instance was loaded successfully and false
     *              if it was not
     *
     * @deprecated use {@link SiteInstance::loadByShortname()} instead
     */
    public function loadFromShortname($shortname)
    {
        return $this->loadByShortname($shortname);
    }

    /**
     * Loads a instance by its shortname.
     *
     * @param string $shortname the shortname of the instance to load
     *
     * @return bool true if the instance was loaded successfully and false
     *              if it was not
     */
    public function loadByShortname($shortname)
    {
        $row = null;

        if ($this->table !== null) {
            $sql = sprintf(
                'select * from %s where shortname = %s',
                $this->table,
                $this->db->quote($shortname, 'text')
            );

            $rs = SwatDB::query($this->db, $sql, null);
            $row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
        }

        if ($row === null) {
            return false;
        }

        $this->initFromRow($row);
        $this->generatePropertyHashes();

        return true;
    }

    protected function init()
    {
        $this->table = 'Instance';
        $this->id_field = 'integer:id';
    }

    // loader methods

    /**
     * Loads the config settings for this instance.
     *
     * @return SiteInstanceConfigSettingWrapper a recordset of config settings
     */
    protected function loadConfigSettings()
    {
        $sql = 'select * from InstanceConfigSetting where instance = %s';
        $sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

        $wrapper = SwatDBClassMap::get(SiteInstanceConfigSettingWrapper::class);
        $settings = SwatDB::query($this->db, $sql, $wrapper);

        $non_default = [];

        // Find all config settings that have non-default values
        foreach ($settings as $setting) {
            if (!$setting->is_default) {
                $non_default[] = $setting->name;
            }
        }

        // Remove all the config settings that have non-default replacements
        foreach ($settings as $setting) {
            if (in_array($setting->name, $non_default)
                && $setting->is_default) {
                $settings->remove($setting);
            }
        }

        return $settings;
    }
}
