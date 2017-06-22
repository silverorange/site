<?php

/**
 * An error logger that sends error details to Sentry
 *
 * @package   Site
 * @copyright 2006-2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSentryErrorLogger extends SiteErrorLogger
{
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
	public function __construct(Raven_Client $client)
	{
		$this->client = $client;
	}

	// }}}
	// {{{ public function log()

	/**
	 * Logs an error
	 *
	 * @param SwatError $e the error to log
	 */
	public function log(SwatError $e)
	{
		if (!$this->filter($e)) {
			$this->client->captureException(
				new ErrorException(
					$e->getMessage(),
					0,
					$e->getSeverity(),
					$e->getFile(),
					$e->getLine()
				)
			);
		}
	}

	// }}}
}

?>
