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





	/**
	 * Creates a new sentry error loggger
	 *
	 */
	public function __construct()
	{
	}




	/**
	 * Logs an error
	 *
	 * @param SwatError $e the error to log
	 */
	public function log(SwatError $e)
	{
		if (!$this->filter($e)) {
			\Sentry\captureException(
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


}

?>
