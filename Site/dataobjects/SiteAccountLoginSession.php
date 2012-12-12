<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/dataobjects/SiteAccount.php';

/**
 * Active sessions for this account. Used for both persistent login via cookie,
 * and to track and display active sessions to the user.
 *
 * @package   Site
 * @copyright 2012 silverorange
 */
class SiteAccountLoginSession extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * @var SwatDate
	 */
	public $createdate;

	/**
	 * @var SwatDate
	 */
	public $login_date;

	/**
	 * @var string
	 */
	public $ip_address;

	/**
	 * @var string
	 */
	public $user_agent;

	/**
	 * @var string
	 */
	public $tag;

	/**
	 * @var string
	 */
	public $session_id;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'AccountLoginSession';
		$this->id_field = 'integer:id';

		$this->registerDateProperty('createdate');
		$this->registerDateProperty('login_date');

		$this->registerInternalProperty('account',
			SwatDBClassMap::get('SiteAccount'));
	}

	// }}}
}

?>
