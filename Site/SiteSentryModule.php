<?php

/**
 * Application module for Sentry error context
 *
 * @package   Site
 * @copyright 2017 silverorange
 */
class SiteSentryModule extends SiteApplicationModule
{
	// {{{ protected properties

	/**
	 * The Sentry client
	 *
	 * @var Raven_Client
	 *
	 * @see SiteSentryModule::getClient()
	 */
	protected $client = null;

	// }}}
	// {{{ public function init()

	public function init()
	{
		$config = $this->app->getModule('SiteConfigModule');

		$this->client = new Raven_Client(
			$config->sentry->dsn,
			array(
				// Default breadcrumb handlers override the error_reporting
				// settings, so we disable them
				'install_default_breadcrumb_handlers' => false,
				// Catch fatal errors with Sentry.
				'install_shutdown_handler' => true,
				'environment' => $config->sentry->environment,
			)
		);

		SwatException::addLogger(new SiteSentryExceptionLogger($this->client));
		SwatError::addLogger(new SiteSentryErrorLogger($this->client));

		// Add fatal error handling.
		$error_handler = new Raven_ErrorHandler($this->client, false, null);
		$error_handler->registerShutdownFunction();
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * @return array an array of {@link SiteApplicationModuleDependency}
	 *                        objects defining the features this module
	 *                        depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();
		$depends[] = new SiteApplicationModuleDependency('SiteConfigModule');
		return $depends;
	}

	// }}}
	// {{{ public function getClient()

	/**
	 * Gets the Sentry client for this module
	 *
	 * @return Raven_Client the sentry client.
	 */
	public function getClient()
	{
		return $this->client;
	}

	// }}}
}

?>
