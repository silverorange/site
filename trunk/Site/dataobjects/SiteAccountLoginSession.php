<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/dataobjects/SiteAccount.php';

/**
 * Active sessions for this account. Used for both persistent login via cookie,
 * and to track and display active sessions to the user.
 *
 * @package   Site
 * @copyright 2012-2013 silverorange
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

	/**
	 * Whether or not the account of this session needs to be reloaded
	 *
	 * @var boolean
	 */
	public $dirty = false;

	// }}}
	// {{{ public function setDirty()

	/**
	 * Flags this session as needing to be reloaded
	 */
	public function setDirty()
	{
		$this->checkDB();

		$sql = sprintf(
			'update AccountLoginSession set dirty = %s where id = %s',
			$this->db->quote(true, 'boolean'),
			$this->db->quote($this->id, 'integer')
		);

		SwatDB::exec($this->db, $sql);
	}

	// }}}
	// {{{ public function setClean()

	/**
	 * Flags this session as NOT needing to be reloaded
	 */
	public function setClean()
	{
		$this->checkDB();

		$sql = sprintf(
			'update AccountLoginSession set dirty = %s where id = %s',
			$this->db->quote(false, 'boolean'),
			$this->db->quote($this->id, 'integer')
		);

		SwatDB::exec($this->db, $sql);
	}

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
