<?php

/**
 * An exception logger that send exception details to Sentry
 *
 * @package   Site
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSentryExceptionLogger extends SiteExceptionLogger {
	// {{{ protected properties

	/**
	 * Client that is used to send exception details to sentry
	 *
	 * Instantiated with dsn elsewhere and then provided in construct
	 *
	 * @var Raven_Client
	 */
	protected $client;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new sentry exception loggger
	 *
	 * @param Raven_Client $client the sentry client to use
	 */

	public function __construct(Raven_Client $client) {
		$this->client = $client;
	}

	// }}}
	// {{{ public function log()

	/**
	 * Logs an exception
	 */
	public function log(SwatException $e) {
		if ($e instanceof SiteNotFoundException)
			return;

		$this->client->captureException($e);
	}

	//}}}
}

