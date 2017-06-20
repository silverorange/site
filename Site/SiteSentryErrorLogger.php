<?php

/**
 * A logger that can be instantiated to send exceptions to Sentry
 *
 * @package   Site
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSentryErrorLogger extends SiteErrorLogger {
	protected $client = null;

	public function __construct(Raven_Client $client) {
		$this->client = $client;
	}

	public function log(SwatError $e) {
		if ($this->filter($e))
			return;

		$ex = new ErrorException($e->getMessage(), 0, $e->getSeverity(), $e->getFile(), $e->getLine());
		$this->client->captureException($ex);
	}
}
