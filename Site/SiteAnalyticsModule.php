<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for handling site analytics.
 *
 * Currently only has support for Google Analytics, but other analytic trackers
 * could be added.
 *
 * @package   Site
 * @copyright 2007-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link      http://code.google.com/apis/analytics/docs/tracking/asyncTracking.html
 */
class SiteAnalyticsModule extends SiteApplicationModule
{
	// {{{ class constants

	/**
	 * Total number of available slots for custom variables.
	 */
	const CUSTOM_VARIABLE_SLOTS = 5;

	/**
	 * Available scopes for custom variables.
	 */
	const CUSTOM_VARIABLE_SCOPE_VISITOR = 1;
	const CUSTOM_VARIABLE_SCOPE_SESSION = 2;
	const CUSTOM_VARIABLE_SCOPE_PAGE    = 3;

	// }}}
	// {{{ protected properties

	/**
	 * Google Analytics Account
	 *
	 * @var string
	 */
	protected $google_account;

	/**
	 * Flag to tell whether the user has opted out of analytics.
	 *
	 * @var boolean
	 */
	protected $analytics_opt_out = false;


	/**
	 * Flag to tell whether to load the enchanced link attribution plugin.
	 *
	 * @var boolean
	 * @link https://support.google.com/analytics/answer/2558867?hl=en&ref_topic=2558810
	 */
	protected $enhanced_link_attribution = false;

	/**
	 * Stack of commands to send to google analytics
	 *
	 * Each entry is an array where the first value is the google analytics
	 * command, and any following values are optional command parameters.
	 *
	 * @var array
	 */
	protected $ga_commands = array();

	// }}}
	// {{{ public function init()

	public function init()
	{
		$this->initOptOut();

		// skip init of the commands if we're opted out.
		if ($this->analytics_opt_out === false)
			$this->initGoogleAnalyticsCommands();
	}

	// }}}
	// {{{ public function setGoogleAccount()

	public function setGoogleAccount($account)
	{
		$this->google_account = $account;
	}

	// }}}
	// {{{ public function setEnhancedLinkAttribution()

	public function setEnhancedLinkAttribution($enhanced_link_attribution)
	{
		$this->enhanced_link_attribution = (bool)$enhanced_link_attribution;
	}

	// }}}
	// {{{ public function enableEnhancedLinkAttribution()

	public function enableEnhancedLinkAttribution()
	{
		$this->setEnhancedLinkAttribution(true);
	}

	// }}}
	// {{{ public function disableEnhancedLinkAttribution()

	public function disableEnhancedLinkAttribution()
	{
		$this->setEnhancedLinkAttribution(false);
	}

	// }}}
	// {{{ public function hasGoogleAnalytics()

	public function hasGoogleAnalytics()
	{
		return ($this->google_account != '' &&
			$this->analytics_opt_out === false);
	}

	// }}}
	// {{{ public function pushGoogleAnalyticsCommand()

	public function pushGoogleAnalyticsCommand($command)
	{
		$this->ga_commands = array_merge($this->ga_commands, $command);
	}

	// }}}
	// {{{ public function prependGoogleAnalyticsCommand()

	public function prependGoogleAnalyticsCommand($command)
	{
		array_unshift($this->ga_commands, $command);
	}

	// }}}
	// {{{ public function getGoogleAnalyticsInlineJavascript()

	public function getGoogleAnalyticsInlineJavascript()
	{
		$javascript = null;

		if ($this->hasGoogleAnalytics() && count($this->ga_commands)) {
			$javascript = sprintf(
				"%s\n%s",
				$this->getGoogleAnalyticsCommandsInlineJavascript(),
				$this->getGoogleAnalyticsTrackerInlineJavascript()
			);
		}

		return $javascript;
	}

	// }}}
	// {{{ public function getGoogleAnalyticsCommandsInlineJavascript()

	public function getGoogleAnalyticsCommandsInlineJavascript()
	{
		$javascript = null;

		if ($this->hasGoogleAnalytics() && count($this->ga_commands)) {
			$commands = null;

			if ($this->enhanced_link_attribution) {
				// Enhanced link attribution plugin comes before _setAccount in
				// Google documentation, so put it first. Note: the plugin URI
				// doesn't load properly from https://ssl.google-analytics.com/.
				$plugin_uri = '//www.google-analytics.com/plugins/ga/'.
					'inpage_linkid.js';

				$commands.= $this->getGoogleAnalyticsCommand(
					array(
						'_require',
						'inpage_linkid',
						$plugin_uri,
					)
				);
			}

			// Always set the account before any further commands.
			$commands.= $this->getGoogleAnalyticsCommand(
				array(
					'_setAccount',
					$this->google_account,
				)
			);

			foreach ($this->ga_commands as $command) {
				$commands.= $this->getGoogleAnalyticsCommand($command);
			}

			$javascript = <<<JS
var _gaq = _gaq || [];
%s
JS;

			$javascript = sprintf(
				$javascript,
				$commands
			);
		}

		return $javascript;
	}

	// }}}
	// {{{ public function getGoogleAnalyticsTrackerInlineJavascript()

	public function getGoogleAnalyticsTrackerInlineJavascript()
	{
		$javascript = null;

		if ($this->hasGoogleAnalytics()) {
			$src = ($this->app->isSecure())
				? 'https://ssl.google-analytics.com/ga.js'
				: 'http://www.google-analytics.com/ga.js';

			$javascript = <<<JS
(function() {
	var ga = document.createElement('script');
	ga.type = 'text/javascript';
	ga.async = true;
	ga.src = '%s';
	var s = document.getElementsByTagName('script')[0];
	s.parentNode.insertBefore(ga, s);
})();
JS;

			$javascript = sprintf(
				$javascript,
				$src
			);
		}

		return $javascript;
	}

	// }}}
	// {{{ protected function initGoogleAnalyticsCommands()

	protected function initGoogleAnalyticsCommands()
	{
		// Default commands for all sites:
		// * Speed sampling 100% of the time.
		// * Track the page view.
		$this->ga_commands = array(
			array(
				'_setSiteSpeedSampleRate',
				100
			),
			'_trackPageview',
		);
	}

	// }}}
	// {{{ protected function initOptOut()

	protected function initOptOut()
	{
		$cookie_module = null;

		if ($this->app->hasModule('SiteCookieModule')) {
			$cookie_module = $this->app->getModule('SiteCookieModule');

			if (isset($cookie_module->AnalyticsOptOut)) {
				$this->analytics_opt_out = true;
			}
		}

		if (isset($_GET['AnalyticsOptIn'])) {
			$this->analytics_opt_out = false;
			if ($cookie_module === null) {
				$e = new SiteException('Attempting to remove Analytics Opt '.
					'Out Cookie with no SiteCookieModule available.');

				$e->processAndContinue();
			} else {
				$cookie_module->removeCookie('AnalyticsOptOut');
			}
		}

		// Opt Out trumps opt in if you include them both flags in your query
		// string for some reason.
		if (isset($_GET['AnalyticsOptOut'])) {
			$this->analytics_opt_out = true;
			if ($cookie_module === null) {
				$e = new SiteException('Attempting to set Analytics Opt Out '.
					'Cookie with no SiteCookieModule available.');

				$e->processAndContinue();
			} else {
				// 10 years should be equivalent to never expiring.
				$cookie_module->setCookie(
					'AnalyticsOptOut',
					'1',
					strtotime('+10 Years')
				);
			}
		}
	}

	// }}}
	// {{{ protected function getGoogleAnalyticsCommand()

	protected function getGoogleAnalyticsCommand($command)
	{
		$method  = null;
		$options = null;

		if (is_array($command)) {
			$method = array_shift($command);

			foreach ($command as $part) {
				$options.= sprintf(
					', %s',
					SwatString::quoteJavaScriptString($part)
				);
			}
		} else {
			$method = $command;
		}

		return sprintf(
			'_gaq.push([%s%s]);',
			SwatString::quoteJavaScriptString($method),
			$options
		);
	}

	// }}}
}

?>
