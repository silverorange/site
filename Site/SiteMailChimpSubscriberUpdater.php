<?php

require_once 'Site/SiteMailingListSubscriberUpdater.php';

/**
 * Cron job application to update newsletter subscribers
 *
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMailChimpSubscriberUpdater
	extends SiteMailingListSubscriberUpdater
{
	// {{{ protected function getList()

	protected function getList()
	{
		return new SiteMailChimpList($this);
	}

	// }}}
	// {{{ protected function handleResult()

	protected function handleResult($result, $success_message)
	{
		parent::handleResult($result, $success_message);

		if (is_array($result)) {
			$this->debug(sprintf($success_message,
				$result['success_count']));

			if ($result['error_count']) {
				$errors = array();
				$not_found_count = 0;
				$bounced_count = 0;
				$previously_unsubscribed_count = 0;
				$invalid_count = 0;
				$queued_count = 0;

				// don't throw errors for codes we know can be ignored.
				foreach ($result['errors'] as $error) {
					switch ($error['code']) {
					case SiteMailChimpList::NOT_FOUND_ERROR_CODE:
					case SiteMailChimpList::NOT_SUBSCRIBED_ERROR_CODE:
						$not_found_count++;
						break;

					case SiteMailChimpList::PREVIOUSLY_UNSUBSCRIBED_ERROR_CODE:
						$previously_unsubscribed_count++;
						break;

					case SiteMailChimpList::BOUNCED_ERROR_CODE:
						$bounced_count++;
						break;

					case SiteMailChimpList::INVALID_ADDRESS_ERROR_CODE:
						$invalid_count++;
						break;

					case SiteMailingList::QUEUED:
						$queued_count++;
						break;

					default:
						$error_message = sprintf(
							Site::_('code: %s - message: %s.'),
							$error['code'],
							$error['message']);

						$errors[]  = $error_message;
						$execption = new SiteException($error_message);
						// don't exit on returned errors
						$execption->process(false);
					}
				}

				if ($not_found_count > 0) {
					$this->debug(sprintf(Site::_('%s addresses not found.').
						"\n",
						$not_found_count));
				}

				if ($previously_unsubscribed_count > 0) {
					$this->debug(sprintf(Site::_('%s addresses have '.
						'previously subscribed, and cannot be resubscribed.').
						"\n",
						$previously_unsubscribed_count));
				}

				if ($bounced_count > 0) {
					$this->debug(sprintf(Site::_('%s addresses have bounced, '.
						'and cannot be resubscribed.')."\n",
						$bounced_count));
				}

				if ($invalid_count > 0) {
					$this->debug(sprintf(Site::_('%s invalid addresses.')."\n",
						$invalid_count));
				}

				if ($queued_count > 0) {
					$this->debug(sprintf(Site::_('%s addresses queued.')."\n",
						$queued_count));
				}

				if (count($errors)) {
					$this->debug(sprintf(Site::_('%s errors:')."\n",
						count($errors)));

					foreach ($errors as $error) {
						$this->debug($error."\n");
					}
				}
			}
		}
	}

	// }}}
}

?>
