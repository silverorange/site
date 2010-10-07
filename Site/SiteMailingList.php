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

	/**
	 * Return Value when unable to subscribe an email address to the list.
	 *
	 * This is returned on invalid email addresses.
	 */
	const INVALID = 6;

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
	// {{{ public function handleSubscribeResponse()

	public function handleSubscribeResponse($response)
	{
		switch ($response) {
		case SiteMailingList::INVALID:
			$message = new SwatMessage(Site::_('Sorry, the email address '.
				'you entered is not a valid email address.'),
				'error');

			break;

		case SiteMailingList::FAILURE:
			$message = new SwatMessage(Site::_('Sorry, there was an issue '.
				'subscribing you to the list.'),
				'error');

			$message->content_type = 'text/xml';
			$message->secondary_content = sprintf(Site::_('This can usually '.
				'be resolved by trying again later. If the issue persists '.
				'please <a href="%s">contact us</a>.'),
				$this->getContactUsLink());

			$message->content_type = 'txt/xhtml';
			break;

		default:
			$message = null;
		}

		return $message;
	}

	// }}}
	// {{{ abstract public function unsubscribe()

	abstract public function unsubscribe($address);

	// }}}
	// {{{ abstract public function batchUnsubscribe()

	abstract public function batchUnsubscribe(array $addresses);

	// }}}
	// {{{ public function handleUnsubscribeResponse()

	public function handleUnsubscribeResponse($response)
	{
		switch ($response) {
		case SiteMailingList::NOT_FOUND:
			$message = new SwatMessage(Site::_('Thank you. Your email address '.
				'was never subscribed to our newsletter.'),
				'notice');

			$message->secondary_content =
				Site::_('You will not receive any mailings to this address.');

			break;

		case SiteMailingList::NOT_SUBSCRIBED:
			$message = new SwatMessage(Site::_('Thank you. Your email address '.
				'has already been unsubscribed from our newsletter.'),
				'notice');

			$message->secondary_content =
				Site::_('You will not receive any mailings to this address.');

			break;

		case SiteMailingList::FAILURE:
			$message = new SwatMessage(Site::_('Sorry, there was an issue '.
				'unsubscribing from the list.'),
				'error');

			$message->content_type = 'text/xml';
			$message->secondary_content = sprintf(Site::_('This can usually '.
				'be resolved by trying again later. If the issue persists '.
				'please <a href="%s">contact us</a>.'),
				$this->getContactUsLink());

			$message->content_type = 'txt/xhtml';
			break;

		default:
			$message = null;
		}

		return $message;
	}

	// }}}
	// {{{ abstract public function isMember()

	abstract public function isMember($address);

	// }}}
	// {{{ abstract public function updateMemberInfo()

	abstract public function updateMemberInfo($address, array $info,
		array $array_map = array());

	// }}}
	// {{{ protected function getContactUsLink()

	protected function getContactUsLink()
	{
		return 'about/contact';
	}

	// }}}

	// campaign methods
	// {{{ abstract public function saveCampaign()

	abstract public function saveCampaign(SiteMailingCampaign $campaign);

	// }}}

	/*
	 * Subscriber queue methods
	 *
	 * Don't worry about dupes in the list, subscribing someone multiple times
	 * doesn't break anything. And if it ever does, we'll handle it in the
	 * code that subscribes the queued requests.
	 */

	// {{{ public function queueSubscribe()

	public function queueSubscribe($address, array $info, $send_welcome = false)
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
	// {{{ public function queueBatchSubscribe()

	public function queueBatchSubscribe(array $addresses, $send_welcome = false)
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
	// {{{ public function queueUnsubscribe()

	public function queueUnsubscribe($address)
	{
		$sql = 'insert into MailingListUnsubscribeQueue (email) values (%s)';
		$sql = sprintf($sql,
			$this->app->db->quote($address, 'text'));

		SwatDB::exec($this->app->db, $sql);

		return SiteMailingList::QUEUED;
	}

	// }}}
	// {{{ public function queueBatchUnsubscribe()

	public function queueBatchUnsubscribe(array $addresses)
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
