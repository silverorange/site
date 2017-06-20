<?php

/**
 * An error logger that sends error details to Sentry
 *
 * @package   Site
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSentryErrorLogger extends SiteErrorLogger {
	// {{{ protected properties

	/**
	 * Client that is used to send error details to sentry
	 *
	 * Instantiated with dsn elsewhere and then provided in construct
	 *
	 * @var Raven_Client
	 */
	protected $client;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new sentry error loggger
	 *
	 * @param Raven_Client $client the sentry client to use
	 */
	public function __construct(Raven_Client $client) {
		$this->client = $client;
	}

	// }}}
	// {{{ public function log()

	/**
	 * Logs an error
	 */
	public function log(SwatError $e) {
		if ($this->filter($e))
			return;

		$ex = new ErrorException($e->getMessage(), 0, $e->getSeverity(), $e->getFile(), $e->getLine());
		$this->client->captureException($ex);
	}

	// }}}
}
