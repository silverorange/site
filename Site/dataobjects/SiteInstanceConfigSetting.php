<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteInstance.php';

/**
 * A dataobject class for site instance config settings
 *
 * @package   Site
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteMultipleInstanceModule
 */
class SiteInstanceConfigSetting extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

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

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'InstanceConfigSetting';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));
	}

	// }}}
}

?>
