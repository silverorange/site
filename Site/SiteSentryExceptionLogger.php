<?php

/**
 * A logger that can be instantiated to send exceptions to Sentry
 *
 * @package   Site
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSentryExceptionLogger extends SiteExceptionLogger {
	protected $client = null;

	public function __construct(Raven_Client $client) {
		$this->client = $client;
	}

	public function log(SwatException $e) {
		$this->client->captureException($e);
	}
}

