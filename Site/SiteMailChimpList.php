<?php

require_once 'XML/RPC2/Client.php';
require_once 'Site/SiteMailingList.php';
require_once 'Site/SiteMailChimpCampaign.php';
require_once 'VanBourgondien/VanBourgondienNewsletter.php';

/**
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMailChimpList extends SiteMailingList
{
	// {{{ class constants

	/**
	 * Error code returned when attempting to subscribe an email address that
	 * has previously unsubscribed. We can't programatically resubscribe them,
	 * mailchimp requires them to resubscribe out of their own volition.
	 */
	const PREVIOUSLY_UNSUBSCRIBED_ERROR_CODE = 212;

	/**
	 * Error code returned when attempting to subscribe an email address that
	 * has bounced in the past, so can't be resubscribed.
	 */
	const BOUNCED_ERROR_CODE = 213;

	/**
	 * Error code returned when attempting to unsubscribe an email address that
	 * is not a current member of the list.
	 */
	const NOT_SUBSCRIBED_ERROR_CODE = 215;

	/**
	 * Error code returned when attempting to unsubscribe an email address that
	 * was never a member of the list.
	 */
	const NOT_FOUND_ERROR_CODE = 232;

	/**
	 * Error code returned when attempting to subscribe an email address that
	 * is not a valid email address.
	 */
	const INVALID_ADDRESS_ERROR_CODE = 502;

	// }}}
	// {{{ protected properties

	protected $client;
	protected $list_merge_array_map = array();

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, $shortname = null)
	{
		parent::__construct($app, $shortname);

		$this->client = XML_RPC2_Client::create('asdas');
//			$this->app->config->mail_chimp->api_url);

		if ($this->shortname === null)
			$this->shortname = $app->config->mail_chimp->default_list;

		$this->initListMergeArrayMap();
	}

	// }}}
	// {{{ public function isAvailable()

	/**
	 * Tests to make sure the service is available.
	 *
	 * Returns false if mailchimp returns an unexpected value or the
	 * XML_RPC2_Client throws an exception. Unexpected values from mailchimp
	 * get thrown in exceptions as well. Any exceptions thrown are not exited
	 * on, so that we can queue requests based on service availability.
	 *
	 * @return boolean whether or not the service is available.
	 */
	public function isAvailable()
	{
		$available = false;

		try {
			$result = $this->client->ping(
				$this->app->config->mail_chimp->api_key);

			// Endearing? Yes. But also annoying to have to check for a string.
			if ($result === "Everything's Chimpy!") {
				$available = true;
			} else {
				// throw whatever the chimp has given us back
				$e = new SiteException($result);
				$e->log();
			}

		} catch (Exception $e) {
			$e = new SiteException($e);
			$e->log();
		}

		return $available;
	}

	// }}}
	// {{{ protected function initListMergeArrayMap()

	protected function initListMergeArrayMap()
	{
		$this->list_merge_array_map = array(
			'email'      => 'EMAIL', // only used for batch subscribes
			'first_name' => 'FNAME',
			'last_name'  => 'LNAME',
			'user_ip'    => 'OPTINIP',
		);
	}

	// }}}

	// subscriber methods
	// {{{ public function subscribe()

	public function subscribe($address, array $info = array(),
		$send_welcome = true, array $array_map = array())
	{
		$result = false;

		if ($this->isAvailable()) {
			// passed in array_map is second so that it can override any of the
			// list_merge_array_map values
			$array_map = array_merge($this->list_merge_array_map, $array_map);

			$merges = array();
			foreach ($info as $id => $value) {
				if (array_key_exists($id, $array_map) && $value != null) {
					$merges[$array_map[$id]] = $value;
				}
			}

			try {
				// last boolean is flag for update_existing
				$result = $this->client->listSubscribe(
					$this->app->config->mail_chimp->api_key,
					$this->shortname,
					$address,
					$merges,
					'html',
					$this->app->config->mail_chimp->double_opt_in,
					true, // update_existing
					true, // replace_interests, this is the default
					$send_welcome
					);

			} catch (XML_RPC2_FaultException $e) {
				$e = new SiteException($e);
				$e->process();
			}
		} elseif ($this->app->hasModule('SiteDatabaseModule')) {
			$result = $this->queueSubscribe($address, $info, $send_welcome);
		}

		if ($result === true) {
			$result = self::SUCCESS;
		} elseif ($result === false) {
			$result = self::FAILURE;
		}

		return $result;
	}

	// }}}
	// {{{ public function batchSubscribe()

	public function batchSubscribe(array $addresses, $send_welcome = false,
		array $array_map = array())
	{
		$result = false;

		if ($this->isAvailable()) {
			// passed in array_map is second so that it can override any of the
			// list_merge_array_map values
			$array_map = array_merge($this->list_merge_array_map, $array_map);

			$merged_addresses = array();
			foreach ($addresses as $address) {
				$merged_address = array();
				foreach ($address as $id => $value) {
					if (array_key_exists($id, $array_map) && $value != null) {
						$merged_address[$array_map[$id]] = $value;
					}
				}

				if (count($merged_address)) {
					$merged_addresses[] = $merged_address;
				}
			}

			// MailChimp doesn't allow welcomes to be sent on batch subscribes.
			// So if we need to send them, do individual subscribes instead.
			if ($send_welcome === true) {
				$result = array(
					'success_count' => 0,
					'error_count'   => 0,
					'errors'        => array(),
					);

				foreach ($merged_addresses as $info) {
					$current_result = $this->subscribe($info['email'], $info,
						$send_welcome, $array_map);

					switch ($curent_result) {
					case self::SUCCESS:
						$result['sucess_count']++;
						break;

					default:
						$result['error_count']++;
						$result['errors'][] = array(
							'code' => $curent_result,
							'message' => Site::_(sprintf('Error subscribing %s',
								$info['email'])),
						);
					}
				}
			} else {
				try {
					$result = $this->client->listBatchSubscribe(
						$this->app->config->mail_chimp->api_key,
						$this->shortname,
						$merged_addresses,
						false, // double_optin
						true,  // update_existing
						true   // replace_intrests, this is the default
						);

				} catch (XML_RPC2_FaultException $e) {
					$e = new SiteException($e);
					$e->process();
				}
			}
		} elseif ($this->app->hasModule('SiteDatabaseModule')) {
			$result = $this->queueBatchSubscribe($addresses, $send_welcome);
		}

		return $result;
	}

	// }}}
	// {{{ public function unsubscribe()

	public function unsubscribe($address)
	{
		$result = false;

		if ($this->isAvailable()) {
			try {
				$result = $this->client->listUnsubscribe(
					$this->app->config->mail_chimp->api_key,
					$this->shortname,
					$address,
					false, // delete_member
					false  // send_goodbye
					);

			} catch (XML_RPC2_FaultException $e) {
				// gracefully handle exceptions that we can provide nice
				// feedback about.
				if ($e->getFaultCode() == self::NOT_FOUND_ERROR_CODE) {
					$result = SiteMailingList::NOT_FOUND;
				} elseif ($e->getFaultCode() ==
					self::NOT_SUBSCRIBED_ERROR_CODE) {
					$result = SiteMailingList::NOT_SUBSCRIBED;
				} else {
					$e = new SiteException($e);
					$e->process();
				}
			}
		} elseif ($this->app->hasModule('SiteDatabaseModule')) {
			$result = $this->queueUnsubscribe($address);
		}

		if ($result === true) {
			$result = self::SUCCESS;
		} elseif ($result === false) {
			$result = self::FAILURE;
		}

		return $result;
	}

	// }}}
	// {{{ public function batchUnsubscribe()

	public function batchUnsubscribe(array $addresses)
	{
		$result = false;

		if ($this->isAvailable()) {
			try {
				$result = $this->client->listBatchUnsubscribe(
					$this->app->config->mail_chimp->api_key,
					$this->shortname,
					$addresses,
					false, // delete_member
					false  // send_goodbye
					);

			} catch (XML_RPC2_FaultException $e) {
				$e = new SiteException($e);
				$e->process();
			}
		} elseif ($this->app->hasModule('SiteDatabaseModule')) {
			$result = $this->queueBatchUnsubscribe($addresses);
		}

		return $result;
	}

	// }}}

	// subscriber queue methods
	// TODO: test what happens when a subscriber is queued multiple times.
	// if it doesn't break anything, just allow it to happen.
	// {{{ protected function queueSubscribe()

	protected function queueSubscribe($address, array $info, $send_welcome)
	{
		$sql = 'insert into MailingListSubscribeQueue
			(email, info, send_welcome) values (%s, %s, %s)';

		SwatDB::exec($this->app->db, sprintf($sql,
			$this->app->db->quote($address, 'text'),
			$this->app->db->quote(serialize($info), 'text'),
			$this->app->db->quote($send_welcome, 'boolean')));

		return SiteMailingList::QUEUED;
	}

	// }}}
	// {{{ protected function queueBatchSubscribe()

	protected function queueBatchSubscribe(array $addresses, $send_welcome)
	{
		$sql = 'insert into MailingListSubscribeQueue
			(email, info, send_welcome) values %s';

		$values = array();
		$send_welcome_quoted = $this->app->db->quote($send_welcome, 'boolean');

		foreach ($addresses as $info) {
			$values[] = sprintf('(%s, %s)',
				$this->app->db->quote($info['email'], 'text'),
				$this->app->db->quote(serialize($info), 'text'),
				$send_welcome_quoted);
		}

		SwatDB::exec($this->app->db, sprintf($sql,
			implode(',', $values)));

		return SiteMailingList::QUEUED;
	}

	// }}}
	// {{{ protected function queueUnsubscribe()

	protected function queueUnsubscribe($address)
	{
		$sql = 'insert into MailingListUnsubscribeQueue (email) values (%s)';

		SwatDB::exec($this->app->db, sprintf($sql,
			$this->app->db->quote($address, 'text')));

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

		SwatDB::exec($this->app->db, sprintf($sql,
			implode(',', $values)));

		return SiteMailingList::QUEUED;
	}

	// }}}

	// campaign methods
	// {{{ public function saveCampaign()

	public function saveCampaign(SiteMailingCampaign $campaign)
	{
		$campaign->id = $this->getCampaignId($campaign);

		if ($campaign->id != null) {
			$this->updateCampaign($campaign);
		} else {
			$this->createCampaign($campaign);
		}
	}

	// }}}
	// {{{ public function getCampaignId()

	public function getCampaignId(SiteMailingCampaign $campaign)
	{
		$campaign_id = null;
		$filters     = array(
			'list_id' => $this->shortname,
			'title'   => $campaign->shortname,
			'exact'   => true,
		);

		try {
			$campaigns = $this->client->campaigns(
				$this->app->config->mail_chimp->api_key,
				$filters);
		} catch (XML_RPC2_FaultException $e) {
			$e = new SiteException($e);
			$e->process();
		}

		// TODO: clean up, and throw error if count > 1
		if (count($campaigns)) {
			$campaign_id = $campaigns[0]['id'];
		}

		return $campaign_id;
	}

	// }}}
	// {{{ public function sendCampaignTest()

	public function sendCampaignTest(SiteMailChimpCampaign $campaign,
		array $test_emails)
	{
		try {
			$this->client->campaignSendTest(
				$this->app->config->mail_chimp->api_key,
				$campaign->id,
				$test_emails);
		} catch (XML_RPC2_FaultException $e) {
			$e = new SiteException($e);
			$e->process();
		}
	}

	// }}}
	// {{{ protected function createCampaign()

	protected function createCampaign(SiteMailingCampaign $campaign)
	{
		$options = $this->getCampaignOptions($campaign);
		$content = $this->getCampaignContent($campaign);

		try {
			$campaign_id = $this->client->campaignCreate(
				$this->app->config->mail_chimp->api_key,
				$campaign->type, $options, $content);
		} catch (XML_RPC2_FaultException $e) {
			$e = new SiteException($e);
			$e->process();
		}

		$campaign->id = $campaign_id;
	}

	// }}}
	// {{{ protected function updateCampaign()

	protected function updateCampaign(SiteMailingCampaign $campaign)
	{
		$options = $this->getCampaignOptions($campaign);
		$content = $this->getCampaignContent($campaign);

		try {
			$this->client->campaignUpdate(
				$this->app->config->mail_chimp->api_key,
				$campaign->id, 'content', $content);

			// options can only be updated one at a time.
			foreach ($options as $title => $value) {
				$this->client->campaignUpdate(
					$this->app->config->mail_chimp->api_key,
					$campaign->id, $title, $value);
			}
		} catch (XML_RPC2_FaultException $e) {
			$e = new SiteException($e);
			$e->process();
		}
	}

	// }}}
	// {{{ protected function getCampaignOptions()

	protected function getCampaignOptions(SiteMailChimpCampaign $campaign)
	{
		$subject = $campaign->getSubject();
		if ($subject == null)
			throw new SiteException('Campaign “Subject” is null');

		$from_address = $campaign->getFromAddress();
		if ($from_address == null)
			throw new SiteException('Campaign “From Address” is null');

		$from_name = $campaign->getFromName();
		if ($from_name == null)
			throw new SiteException('Campaign “From Name” is null');

		$analytics = '';
		$key = $campaign->getAnalyticsKey();
		if ($key != null)
			$analytics = array('google' => $key);

		$options = array(
			'list_id'      => $this->shortname,
			'title'        => $campaign->shortname,
			'subject'      => $subject,
			'from_email'   => $from_address,
			'from_name'    => $from_name,
			'to_email'     => '*|FNAME|* *|LNAME|*',
			'authenticate' => 'true',
			'analytics'    => $analytics,
			'inline_css'   => true,
		);

		if ($this->app->config->mail_chimp->default_folder != null) {
			$options['folder_id'] =
				$this->app->config->mail_chimp->default_folder;
		}

		return $options;
	}

	// }}}
	// {{{ protected function getCampaignContent()

	protected function getCampaignContent(SiteMailChimpCampaign $campaign)
	{
		$content = array(
			'html' => $campaign->getContent(SiteMailingCampaign::FORMAT_XHTML),
			'text' => $campaign->getContent(SiteMailingCampaign::FORMAT_TEXT),
		);

		return $content;
	}

	// }}}

	// setup helper methods.
	// {{{ public static function getLists()

	public static function getLists(SiteApplication $app)
	{
		$lists = null;

		try {
		    $lists = $this->client->lists($app->config->mail_chimp->api_key);
		} catch (XML_RPC2_FaultException $e) {
			$e = new SiteException($e);
			$e->process();
		}

		return $lists;
	}

	// }}}
	// {{{ public static function getFolders()

	public static function getFolders(SiteApplication $app)
	{
		$folders = null;

		try {
		    $folders = $client->campaignFolders(
				$app->config->mail_chimp->api_key);

		} catch (XML_RPC2_FaultException $e) {
			$e = new SiteException($e);
			$e->process();
		}

		return $folders;
	}

	// }}}
	// {{{ public function getlistMergeVars()

	public function getlistMergeVars()
	{
		$merge_vars = null;

		try {
			$merge_vars = $this->client->listMergeVars(
				$this->app->config->mail_chimp->api_key, $this->shortname);

		} catch (XML_RPC2_FaultException $e) {
			$e = new SiteException($e);
			$e->process();
		}

		return $merge_vars;
	}

	// }}}
}

?>

