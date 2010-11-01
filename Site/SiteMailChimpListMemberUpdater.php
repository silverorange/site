<?php

require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteMailChimpList.php';

/**
 * Updates all members of a list
 *
 * Pseudo-Code
 * - if update member cache: query subscribers, update cache table
 * - query all subscribers in cache that aren't in updater cache
 * - foreach subscriber, get details, query fields, update fields, add to array
 *  - if api_calls > threshold or all members update, save cache and queue batch
 * - query cache table, present results
 *
 * Note: This doesn't actually run the updates, just sticks them in the queue.
 * Queue must manually be cleared (or left to the cron).
 *
 * @package   Site
 * @copyright 2009-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      Don't queue updates when the field already exists. Make sure
 *            queued subscribes haven't unsubscribed? Now that getting the
 *            member list respects segment options, we can make this much
 *            smarter, and only grab subsets of members that haven't been
 *            updated.
 */

abstract class SiteMailChimpListMemberUpdater extends SiteCommandLineApplication
{
	// {{{ class constants

	/**
	 * Number of members to query from MailChimp per request.
	 *
	 * 100 is the api default, 15000 the max.
	 *
	 * @var integer
	 */
	const CHUNK_SIZE = 15000;

	/**
	 * How often to save updated Member information.
	 *
	 * If $this->api_calls reaches this threshold, we batch save any updates up
	 * until this point, and then save each update individually after that. If
	 * $this->api_calls doesn't reach this threshold, all the updates are batch
	 * queued after all the members have been updated. Experience shows
	 * XML/RPC2 segfaulting at ~425 api calls, so set it a bit lower to make
	 * sure we loose as few updates as possible.
	 *
	 * @var integer
	 */
	const API_CALL_THRESHOLD = 425;

	// }}}
	// {{{ protected properties

	/**
	 * The list we're segmenting.
	 *
	 * @var SiteMailChimpList
	 */
	protected $list;

	/**
	 * Whether or not to incrementally update the members.
	 *
	 * If true, we only update members of the list that don't already been
	 * updated. If false, we update every subscriber.
	 *
	 * @var boolean
	 */
	protected $incremental = true;

	/**
	 * Whether or not to update the member cache
	 *
	 * If true, we update the member cache table, if false, we skip the cache
	 * update.
	 *
	 * @var boolean
	 */
	protected $update_member_cache = false;

	/**
	 * Current count of API Calls.
	 *
	 * Whenever the api is called, this is incremented. Mostly for debug
	 * purposes.
	 *
	 * @var integer
	 */
	protected $api_calls = 0;

	/**
	 * Field we're updating
	 *
	 * @var array
	 */
	protected $field;

	/**
	 * Merge Var for $field.
	 *
	 * @var array
	 */
	protected $merge;

	/**
	 * Merge Var Array for the segment field
	 *
	 * This is always an array of $field => $merge
	 *
	 * @var array
	 */
	protected $array_map = array();

	/**
	 * Results we want to track and display in the summary.
	 *
	 * @var array
	 */
	protected $results = array();

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		parent::__construct($id, $filename, $title, $documentation);

		$this->verbosity = self::VERBOSITY_ALL;

		$incremental = new SiteCommandLineArgument(
			array('-i', '--incremental'),
			'setIncremental',
			'Sets whether to only use resources from s3.');

		$this->addCommandLineArgument($incremental);

		$update_member_cache = new SiteCommandLineArgument(
			array('-u', '--update-member-cache'),
			'setUpdateMemberCache',
			'Sets whether to only use resources from s3.');

		$this->addCommandLineArgument($update_member_cache);

		$field = new SiteCommandLineArgument(
			array('-f', '--field'),
			'setField',
			'Sets the field that we’re updating. Will also set the '.
			'merge as the uppercase version of the field.');

		$field->addParameter('string',
			'--field expects a single paramater.');

		$this->addCommandLineArgument($field);

		$merge = new SiteCommandLineArgument(
			array('-m', '--merge'),
			'setMerge',
			'Sets the merge for the field.');

		$merge->addParameter('string',
			'--merge expects a single paramater.');

		$this->addCommandLineArgument($merge);

		$this->initModules();
		$this->parseCommandLineArguments();
		$this->initList();
		$this->initArrayMap();
		$this->initResultsArray();
	}

	// }}}
	// {{{ public function setIncremental()

	public function setIncremental($incremental)
	{
		$this->incremental = $incremental;
	}

	// }}}
	// {{{ public function setUpdateMemberCache()

	public function setUpdateMemberCache($update_member_cache)
	{
		$this->update_member_cache = $update_member_cache;
	}

	// }}}
	// {{{ public function setField()

	public function setField($field)
	{
		$this->field = $field;
		// note, this is lazy, but works 99% of the time.
		$this->merge = strtoupper($field);
	}

	// }}}
	// {{{ public function setMerge()

	public function setMerge($merge)
	{
		$this->merge = $merge;
	}

	// }}}

	// init phase
	// {{{ protected function initList()

	protected function initList()
	{
		// long custom timeout
		$this->list = new SiteMailChimpList($this, null, 90000);
	}

	// }}}
	// {{{ protected function initArrayMap()

	protected function initArrayMap()
	{
		$this->array_map = array(
			$this->field => $this->merge,
			);
	}

	// }}}
	// {{{ protected function initResultsArray()

	protected function initResultsArray()
	{
		$this->results = array(
			'total_count'   => $this->list->getMemberCount(),
			'updated_count' => 0,
			);

		$this->api_calls++;
	}

	// }}}

	// run phase
	// {{{ public function run()

	public function run()
	{
		$this->lock();

		$this->debug(sprintf("Updating %s list members.\n",
			($this->incremental) ? 'new' : 'all'));

		$this->updateMemberCache();
		$this->updateMembers();
		$this->displaySummary();
		$this->displayQueue();
	}

	// }}}
	// {{{ protected function updateMemberCache()

	protected function updateMemberCache()
	{
		if ($this->update_member_cache === true) {
			$this->debug("Updating Member Cache… ");
			$sql = 'select count(id) from MailingListMemberCache';
			$cache_count = SwatDB::queryOne($this->db, $sql);

			// comparing counts isn't a perfect comparison metric, but its good
			// enough for our purposes here.
			if ($cache_count != $this->results['total_count']) {
				$email_addresses = array();
				$members         = $this->list->getMembers();

				$this->api_calls++;

				if (count($members)) {
					foreach ($members as $member) {
						$email_addresses['Email Address'] = sprintf('(%s)',
							$this->db->quote($member['email'], 'text'));
					}

					// attempt to keep memory usage down.
					unset($members);
				}

				// instead of selectively updating the cache, just blow it away
				// and reinsert everything.
				$sql = 'drop index mailinglistmembercache_email_index';
				SwatDB::exec($this->db, $sql);
				$sql = 'truncate MailingListMemberCache';
				SwatDB::exec($this->db, $sql);

				if (count($email_addresses)) {
					$sql = 'insert into MailingListMemberCache
						(email) values %s';

					$sql = sprintf($sql,
						implode(',', $email_addresses));

					SwatDB::exec($this->db, $sql);
				}

				$sql = 'create index MailingListMemberCache_email_index on
					MailingListMemberCache(email)';

				SwatDB::exec($this->db, $sql);

				$this->debug("analyzing… ");
				$sql = 'analyze MailingListMemberCache';
				SwatDB::exec($this->db, $sql);

				// attempt to keep memory usage down.
				unset($email_addresses);
			}

			$this->debug("Done.\n");
		}
	}

	// }}}
	// {{{ protected function updateMembers()

	protected function updateMembers()
	{
		if ($this->incremental === false) {
			$this->debug("Clearing Update Cache… ");
			$sql = 'delete from MailingListMemberUpdaterCache where field = %s';
			$sql = sprintf($sql,
				 $this->db->quote($this->field, 'text'));

			SwatDB::exec($this->db, $sql);
			$this->debug("Done.\n");
		}

		$updates = array();
		$members = $this->getMembers();
		$member_count = count($members);

		$this->debug(sprintf("%s Members to Update.\n",
			SwatString::numberFormat($member_count)));

		$count = 0;
		foreach ($members as $member) {
			$count++;
			$updated_member = $this->updateMember($member->email);
			$updates[]      = $updated_member;

			$this->debug(sprintf("Member %s (%s api calls) - %s: %s - %s\n",
				$count,
				$this->api_calls,
				$this->field,
				$updated_member[$this->field],
				$updated_member['email']));

			if ($this->api_calls > self::API_CALL_THRESHOLD ||
				$count == $member_count) {

				$this->updateMemberUpdaterCache($updates);
				$this->list->queueBatchSubscribe($updates);
				$updates = array();
			}
		}
	}

	// }}}
	// {{{ protected function getMembers()

	protected function getMembers()
	{
		$this->debug("Querying Members… ");
		$this->debug("analyzing cache… ");
		$sql = 'analyze MailingListMemberUpdaterCache';
		SwatDB::exec($this->db, $sql);

		$this->debug("querying… ");
		// for large lists, EXCEPT is much faster than not in.
		$sql = 'select email from MailingListMemberCache
				except
			select email from MailingListMemberUpdaterCache where field = %s';

		$sql = sprintf($sql,
			$this->db->quote($this->field, 'text'));

		$members = SwatDB::query($this->db, $sql);
		$this->debug("Done.\n");

		return $members;
	}

	// }}}
	// {{{ protected function updateMember()

	protected function updateMember($email)
	{
		$member_info = $this->getMemberInfo($email);
		$update = array(
			'email'      => $email,
			'rating'     => $member_info['member_rating'],
			$this->field => $this->getUpdatedField($member_info),
			);

		return $update;
	}

	// }}}
	// {{{ protected function getMemberInfo()

	protected function getMemberInfo($email)
	{
		$member_info = $this->list->getMemberInfo($email);
		$this->api_calls++;

		return $member_info;
	}

	// }}}
	// {{{ abstract protected function getUpdatedField()

	abstract protected function getUpdatedField(array $member_info);

	// }}}
	// {{{ protected function updateMemberUpdaterCache()

	protected function updateMemberUpdaterCache(array $updates)
	{
		$sql = 'insert into MailingListMemberUpdaterCache
			(email, rating, field, value) values %s';

		$values = array();

		foreach ($updates as $info) {
			$values[] = sprintf('(%s, %s, %s, %s)',
				$this->db->quote($info['email'], 'text'),
				$this->db->quote($info['rating'], 'integer'),
				$this->db->quote($this->field, 'text'),
				$this->db->quote($info[$this->field], 'text'));
		}

		$sql = sprintf($sql,
			implode(',', $values));

		SwatDB::exec($this->db, $sql);

		$this->results['updated_count']+= count($updates);
	}

	// }}}
	// {{{ protected function displaySummary()

	protected function displaySummary()
	{
		// Theoretical TODO: do sanity checks and throw warnings on expected
		// totals versus actual, total count versus existing+updated counts,
		// etc.
		// var_dump($results);

		$this->debug(sprintf("\n%s of %s Members Updated\n",
			SwatString::numberFormat($this->results['updated_count']),
			SwatString::numberFormat($this->results['total_count'])),
			true);
	}

	// }}}
	// {{{ protected function displayQueue()

	protected function displayQueue()
	{
		$sql = 'select count(id) from MailingListSubscribeQueue';
		$queued_updates = SwatDB::queryOne($this->db, $sql);

		if ($queued_updates > 0) {
			$this->debug(sprintf(
				"\n%s Member Updates Queued. Remember to run the updater.\n",
				SwatString::numberFormat($queued_updates)),
				true);
		}
	}

	// }}}
}

?>
