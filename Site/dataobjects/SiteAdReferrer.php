<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteAd.php';

/**
 * Tracked inbound ad referrer
 *
 * @package   Site
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAd
 */
class SiteAdReferrer extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier for this ad referrer
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The date this referral occurred
	 *
	 * @var SwatDate
	 */
	public $createdate;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'AdReferrer';
		$this->id_field = 'integer:id';
		$this->registerDateProperty('createdate');
		$this->registerInternalProperty('ad', 'SiteAd');
	}

	// }}}
}

?>
