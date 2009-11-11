<?php

/**
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteMailingList
{
	// {{{ class constants

	/**
	 * Return Value when successfully subscribing or unsubscribing an email
	 * address from the list.
	 */
	const SUCCESS = 1;

	/**
	 * Return Value when unsuccessfully subscribing or unsubscribing an email
	 * address from the list and we have no further information.
	 */
	const FAILURE = 2;

	/**
	 * Return Value when unsuccessfully unsubscribing an email address from the
	 * list.
	 *
	 * This is returned if we know the address was never a member of the
	 * list, or when we have less information, and know the unsubscribe failed.
	 */
	const NOT_FOUND = 3;

	/**
	 * Return Value when unsuccessfully unsubscribing an email address from the
	 * list.
	 *
	 * This is returned if we know the address was a member that has already
	 * unsubscribed from the list.
	 */
	const NOT_SUBSCRIBED = 4;

	/**
	 * Return Value when unable to subscribed/unsubscribe an email address from
	 * the list, but we've been able to queue the request.
	 *
	 * This happens if isAvailable() returns false.
	 */
	const QUEUED = 5;

	// }}}
	// {{{ protected properties

	protected $app;
	protected $shortname;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, $shortname = null)
	{
		$this->app       = $app;
		$this->shortname = $shortname;
	}

	// }}}
	// {{{ abstract public function isAvailable()

	abstract public function isAvailable();

	// }}}

	// subscriber methods
	// {{{ abstract public function subscribe()

	abstract public function subscribe($address, array $info = array(),
		$send_welcome = true, array $array_map = array());

	// }}}
	// {{{ abstract public function batchSubscribe()

	abstract public function batchSubscribe(array $addresses,
		$send_welcome = false, array $array_map = array());

	// }}}
	// {{{ abstract public function unsubscribe()

	abstract public function unsubscribe($address);

	// }}}
	// {{{ abstract public function batchUnsubscribe()

	abstract public function batchUnsubscribe(array $addresses);

	// }}}

	// campaign methods
	// {{{ abstract public function saveCampaign()

	abstract public function saveCampaign(SiteMailingCampaign $campaign);

	// }}}
}

?>
