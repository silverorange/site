<?php

/**
 * An exception logger that send exception details to Sentry
 *
 * @package   Site
 * @copyright 2006-2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSentryExceptionLogger extends SiteExceptionLogger
{


	/**
	 * Creates a new sentry exception loggger
	 *
	 */
	public function __construct()
	{
	}




	/**
	 * Logs an exception
	 *
	 * @param SwatException $e the exception to log
	 */
	public function log(SwatException $e)
	{
		if (!$e instanceof SiteNotFoundException) {
			\Sentry\captureException($e);
		}
	}

	//}}}
}

?>
