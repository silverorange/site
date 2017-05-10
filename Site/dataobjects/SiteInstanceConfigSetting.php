<?php

/**
 * A dataobject class for site instance config settings
 *
 * @package   Site
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteConfigModule
 */
class SiteInstanceConfigSetting extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * The qualified name of the config setting
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The value of the config setting
	 *
	 * @var string
	 */
	public $value;

	/**
	 * Whether or not this is a default value
	 *
	 * @var boolean
	 */
	public $is_default;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'InstanceConfigSetting';

		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));
	}

	// }}}
}

?>
