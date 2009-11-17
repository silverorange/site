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
	// {{{ abstract public function isMember()

	abstract public function isMember($address);

	// }}}

	// campaign methods
	// {{{ abstract public function saveCampaign()

	abstract public function saveCampaign(SiteMailingCampaign $campaign);

	// }}}

	/**
	 * Subscriber queue methods
	 *
	 * Don't worry about dupes in the list, subscribing someone multiple times
	 * doesn't break anything. And if it ever does, we'll handle it in the
	 * code that subscribes the queued requests.
	 */
	// {{{ protected function queueSubscribe()

	protected function queueSubscribe($address, array $info, $send_welcome)
	{
		$sql = 'insert into MailingListSubscribeQueue
			(email, info, send_welcome) values (%s, %s, %s)';

		$sql = sprintf($sql,
			$this->app->db->quote($address, 'text'),
			$this->app->db->quote(serialize($info), 'text'),
			$this->app->db->quote($send_welcome, 'boolean'));

		SwatDB::exec($this->app->db, $sql);

		return SiteMailingList::QUEUED;
	}

	// }}}
	// {{{ protected function queueBatchSubscribe()

	protected function queueBatchSubscribe(array $addresses, $send_welcome)
	{
		$sql = 'insert into MailingListSubscribeQueue
			(email, info) values %s';

		$values = array();
		$send_welcome_quoted = $this->app->db->quote($send_welcome, 'boolean');

		foreach ($addresses as $info) {
			$values[] = sprintf('(%s, %s)',
				$this->app->db->quote($info['email'], 'text'),
				$this->app->db->quote(serialize($info), 'text'),
				$send_welcome_quoted);
		}

		$sql = sprintf($sql,
			implode(',', $values));

		SwatDB::exec($this->app->db, $sql);

		return SiteMailingList::QUEUED;
	}

	// }}}
	// {{{ protected function queueUnsubscribe()

	protected function queueUnsubscribe($address)
	{
		$sql = 'insert into MailingListUnsubscribeQueue (email) values (%s)';
		$sql = sprintf($sql,
			$this->app->db->quote($address, 'text'));

		SwatDB::exec($this->app->db, $sql);

		return SiteMailingList::QUEUED;
	}

	// }}}
	// {{{ protected function queueBatchUnsubscribe()

	protected function queueBatchUnsubscribe(array $addresses)
	{
		$sql = 'insert into MailingListUnsubscribeQueue (email) values %s';
		$values = array();
		foreach ($addresses as $address) {
			$values[] = sprintf('(%s)',
				$this->app->db->quote($address, 'text'));
		}

		$sql = sprintf($sql,
			implode(',', $values));

		SwatDB::exec($this->app->db, $sql);

		return SiteMailingList::QUEUED;
	}

	// }}}
}

?>
