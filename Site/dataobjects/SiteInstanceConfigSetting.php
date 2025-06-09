<?php

/**
 * A dataobject class for site instance config settings.
 *
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SiteConfigModule
 *
 * @property string       $name
 * @property string       $value
 * @property bool         $is_default
 * @property SiteInstance $instance
 */
class SiteInstanceConfigSetting extends SwatDBDataObject
{
    /**
     * The qualified name of the config setting.
     *
     * @var string
     */
    public $name;

    /**
     * The value of the config setting.
     *
     * @var string
     */
    public $value;

    /**
     * Whether or not this is a default value.
     *
     * @var bool
     */
    public $is_default;

    protected function init()
    {
        $this->table = 'InstanceConfigSetting';

        $this->registerInternalProperty(
            'instance',
            SwatDBClassMap::get(SiteInstance::class)
        );
    }
}
