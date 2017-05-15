<?php

/**
 * @package   Site
 * @copyright 2011-2016 silverorange
 */
class SiteAccountLoginHistory extends SwatDBDataObject
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
	public $login_date;

	/**
	 * @var string
	 */
	public $ip_address;

	/**
	 * @var string
	 */
	public $user_agent;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'AccountLoginHistory';
		$this->id_field = 'integer:id';

		$this->registerDateProperty('login_date');

		$this->registerInternalProperty('account',
			SwatDBClassMap::get('SiteAccount'));
	}

	// }}}
}

?>
