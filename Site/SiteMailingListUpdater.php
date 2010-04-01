<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Site/Site.php';
require_once 'Site/SiteMailingList.php';
require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteConfigModule.php';

/**
 * Cron job application to update mailing list with new and queued subscriber
 * requests.
 *
 * @package   Site
 * @copyright 2009-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteMailingListUpdater extends SiteCommandLineApplication
{
	// {{{ protected properties

	protected $dry_run = false;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		parent::__construct($id, $filename, $title, $documentation);

		$dry_run = new SiteCommandLineArgument(array('--dry-run'),
			'setDryRun', 'No data is actually modified.');

		$this->addCommandLineArgument($dry_run);
	}

	// }}}
	// {{{ public function setDryRun()

	public function setDryRun($dry_run)
	{
		$this->dry_run = (boolean)$dry_run;
	}

	// }}}
	// {{{ public function run()

	public function run()
	{
		parent::run();

		$list = $this->getList();

		$this->debug(Site::_('Updating Mailing List')."\n\n", true);

		$this->debug(Site::_('Subscribing:')."\n--------------------\n");
		$this->subscribe($list);
		$this->debug(Site::_('Done subscribing.')."\n\n");

		$this->debug(Site::_('Unsubscribing:')."\n--------------------\n");
		$this->unsubscribe($list);
		$this->debug(Site::_('Done unsubscribing.')."\n\n");

		$this->debug(Site::_('All Done.')."\n", true);
	}

	// }}}
	// {{{ abstract protected function getList()

	abstract protected function getList();

	// }}}
	// {{{ protected function subscribe()

	protected function subscribe(SiteMailingList $list)
	{
		if ($list->isAvailable()) {
			// broken into two methods since we sometimes have to use different
			// api calls to send the welcome email.
			$this->subscribeQueuedWithWelcome($list);
			$this->subscribeQueued($list);
		} else {
			$this->debug(Site::_('Mailing list unavailable. No queued '.
				'addresses subscribed.')."\n");
		}
	}

	// }}}
	// {{{ protected function unsubscribe()

	protected function unsubscribe(SiteMailingList $list)
	{
		if ($list->isAvailable()) {
			$this->unsubscribeQueued($list);
		} else {
			$this->debug(Site::_('Mailing list unavailable. No queued '.
				'addresses unsubscribed.')."\n");
		}
	}

	// }}}
	// {{{ protected function subscribeQueuedWithWelcome()

	protected function subscribeQueuedWithWelcome(SiteMailingList $list)
	{
		$with_welcome = true;
		$addresses = $this->getQueuedSubscribes($with_welcome);

		if (count($addresses) == 0) {
			$this->debug(Site::_('No queued addresses with welcome message '.
				'to subscribe.')."\n");

			return;
		}

		$this->debug(sprintf(
			Site::_('Subscribing %s queued addresses with welcome message.').
			"\n",
			count($addresses)));

		if ($this->dry_run === false) {
			$result = $list->batchSubscribe($addresses, true,
				$this->getArrayMap());

			$clear_queued = $this->handleResult($result,
				Site::_('%s queued addresses with welcome message subscribed.').
				"\n");

			// don't clean the queued subscribes if they have been re-queued.
			if ($clear_queued === true) {
				$this->clearQueuedSubscribes($addresses, $with_welcome);
			}
		}

		$this->debug(Site::_('done subscribing queued addresses with welcome '.
			'message.')."\n\n");
	}

	// }}}
	// {{{ protected function subscribeQueued()

	protected function subscribeQueued(SiteMailingList $list)
	{
		$with_welcome = false;
		$addresses = $this->getQueuedSubscribes($with_welcome);

		if (count($addresses) == 0) {
			$this->debug(Site::_('No queued addresses to subscribe.')."\n");
			return;
		}

		$this->debug(sprintf(Site::_('Subscribing %s queued addresses.')."\n",
			count($addresses)));

		if ($this->dry_run === false) {
			$result = $list->batchSubscribe($addresses, false,
				$this->getArrayMap());

			$clear_queued = $this->handleResult($result,
				Site::_('%s queued addresses subscribed.')."\n");

			// don't clean the queued subscribes if they have been re-queued.
			if ($clear_queued === true) {
				$this->clearQueuedSubscribes($addresses, $with_welcome);
			}
		}

		$this->debug(Site::_('done subscribing queued addresses.')."\n\n");
	}

	// }}}
	// {{{ protected function unsubscribeQueued()

	protected function unsubscribeQueued(SiteMailingList $list)
	{
		$addresses = $this->getQueuedUnsubscribes();

		if (count($addresses) == 0) {
			$this->debug(Site::_('No queued addresses to unsubscribe.')."\n");
			return;
		}

		$this->debug(sprintf(Site::_('Unsubscribing %s queued addresses.')."\n",
			count($addresses)));

		if ($this->dry_run === false) {
			$result = $list->batchUnsubscribe($addresses);

			$clear_queued = $this->handleResult($result,
				Site::_('%s queued addresses unsubscribed.')."\n");

			// don't clean the queued subscribes if they have been re-queued.
			if ($clear_queued === true) {
				$this->clearQueuedUnsubscribes($addresses);
			}
		}

		$this->debug(Site::_('done unsubscribing queued addresses.')."\n\n");
	}

	// }}}
	// {{{ protected function handleResult()

	protected function handleResult($result, $success_message)
	{
		$clear_queued = false;

		if ($result === SiteMailingList::QUEUED) {
			$this->debug(Site::_('All requests queued.')."\n");
		} elseif ($result === SiteMailingList::SUCCESS) {
			$this->debug(Site::_('All requests successful.')."\n");
			$clear_queued = true;
		} elseif (is_int($result) && $result > 0) {
			$this->debug(sprintf($success_message, $result));
		}

		return $clear_queued;
	}

	// }}}
	// {{{ protected function getArrayMap()

	protected function getArrayMap()
	{
		return array();
	}

	// }}}
	// {{{ protected function getQueuedSubscribes()

	private function getQueuedSubscribes($with_welcome)
	{
		$addresses = array();

		$sql = 'select email, info from MailingListSubscribeQueue
			where send_welcome = %s';

		$sql = sprintf($sql,
			$this->db->quote($with_welcome, 'boolean'));

		$rows = SwatDB::query($this->db, $sql);
		foreach ($rows as $row) {
			$address          = unserialize($row->info);
			$address['email'] = $row->email;

			$addresses[] = $address;
		}

		return $addresses;
	}

	// }}}
	// {{{ protected function getQueuedUnsubscribes()

	protected function getQueuedUnsubscribes()
	{
		$addresses = array();

		$sql = 'select email from MailingListUnsubscribeQueue';

		$rows = SwatDB::query($this->db, $sql);
		foreach ($rows as $row) {
			$addresses[] = $row->email;
		}

		return $addresses;
	}

	// }}}
	// {{{ protected function clearQueuedSubscribes()

	protected function clearQueuedSubscribes(array $addresses, $with_welcome)
	{
		$sql = 'delete from MailingListSubscribeQueue
			where email in (%s) and send_welcome = %s';

		$quoted_address_array = array();
		foreach ($addresses as $address) {
			$quoted_address_array[] = $this->db->quote($address['email'],
				'text');
		}

		$sql = sprintf($sql,
			implode(',', $quoted_address_array),
			$this->db->quote($with_welcome, 'boolean'));

		$delete_count = SwatDB::exec($this->db, $sql);

		$this->debug(sprintf(
			Site::_('%s rows (%s addresses) cleared from the queue.')."\n",
			$delete_count, count($addresses)));
	}

	// }}}
	// {{{ protected function clearQueuedUnsubscribes()

	protected function clearQueuedUnsubscribes(array $addresses)
	{
		$sql = 'delete from MailingListUnsubscribeQueue where email in (%s)';

		$quoted_address_array = array();
		foreach ($addresses as $address) {
			$quoted_address_array[] = $this->db->quote($address, 'text');
		}

		$sql = sprintf($sql,
			implode(',', $quoted_address_array));

		$delete_count = SwatDB::exec($this->db, $sql);

		$this->debug(sprintf(
			Site::_('%s rows (%s addresses) cleared from the queue.')."\n",
			$delete_count, count($addresses)));

	}

	// }}}

	// boilerplate
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		$list = parent::getDefaultModuleList();
		$list['config']   = 'SiteConfigModule';
		$list['database'] = 'SiteDatabaseModule';

		return $list;
	}

	// }}}
}

?>
