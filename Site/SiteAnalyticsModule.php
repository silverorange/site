<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for handling site analytics.
 *
 * Currently only has support for Google Analytics and Facebook Pixels.
 *
 * @package   Site
 * @copyright 2007-2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link      http://code.google.com/apis/analytics/docs/tracking/asyncTracking.html
 * @link      https://developers.facebook.com/docs/facebook-pixel/api-reference
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
	 * @link https://support.google.com/analytics/answer/2558867
	 */
	protected $enhanced_link_attribution = false;

	/**
	 * Flag to tell whether to use the display advertisor features.
	 *
	 * These are used for demographic and interest reports on GA, as well as
	 * remarketing and Google Display Network impression reporting.
	 *
	 * @var boolean
	 * @link https://support.google.com/analytics/answer/2444872
	 */
	protected $display_advertising = false;

	/**
	 * Stack of commands to send to google analytics
	 *
	 * Each entry is an array where the first value is the google analytics
	 * command, and any following values are optional command parameters.
	 *
	 * @var array
	 */
	protected $ga_commands = array();

	/**
	 * Facebook Pixel Account
	 *
	 * @var string
	 */
	protected $facebook_pixel_id;

	/**
	 * Stack of commands to send to facebook pixels
	 *
	 * Each entry is an array where the first value is the facebook pixel
	 * command, and any following values are optional command parameters.
	 *
	 * @var array
	 */
	protected $facebook_pixel_commands = array();

	// }}}
	// {{{ public function init()

	public function init()
	{
		$config = $this->app->getModule('SiteConfigModule');

		$this->google_account = $config->analytics->google_account;
		$this->enhanced_link_attribution =
			$config->analytics->google_enhanced_link_attribution;

		$this->display_advertising =
			$config->analytics->google_display_advertising;

		$this->facebook_pixel_id = $config->analytics->facebook_pixel_id;

		$this->initOptOut();

		// skip init of the commands if we're opted out.
		if (!$this->analytics_opt_out) {
			$this->initGoogleAnalyticsCommands();
			$this->initFacebookPixelCommands();
		}
	}

	// }}}
	// {{{ public function hasAnalytics()

	public function hasAnalytics()
	{
		return ($this->hasGoogleAnalytics() || $this->hasFacebookPixel());
	}

	// }}}

	// {{{ public function hasGoogleAnalytics()

	public function hasGoogleAnalytics()
	{
		return (
			$this->google_account != '' &&
			!$this->analytics_opt_out
		);
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
				$this->getGoogleAnalyticsTrackingCodeSource()
			);
		}

		return $javascript;
	}

	// }}}
	// {{{ public function hasFacebookPixel()

	public function hasFacebookPixel()
	{
		return (
			$this->facebook_pixel_id != '' &&
			!$this->analytics_opt_out
		);
	}

	// }}}
	// {{{ public function pushFacebookPixelCommand()

	public function pushFacebookPixelCommand($command)
	{
		$this->facebook_pixel_commands = array_merge(
			$this->facebook_pixel_commands,
			$command
		);
	}

	// }}}
	// {{{ public function prependFacebookPixelCommand()

	public function prependFacebookPixelCommand($command)
	{
		array_unshift($this->facebook_pixel_commands, $command);
	}

	// }}}
	// {{{ public function getFacebookPixelImage()

	public function getFacebookPixelImage()
	{
		return sprintf(
			'<noscript><img height="1" width="1" style="display:none"
			src="https://www.facebook.com/tr?id=%s&ev=PageView&noscript=1"
			/></noscript>',
			SwatString::minimizeEntities($this->facebook_pixel_id)
		);
	}

	// }}}
	// {{{ public function getFacebookPixelInlineJavascript()

	public function getFacebookPixelInlineJavascript()
	{
		$javascript = null;

		if ($this->hasFacebookPixel() &&
			count($this->facebook_pixel_commands)) {
			$javascript = sprintf(
				"%s\n%s",
				$this->getFacebookPixelTrackerInlineJavascript(),
				$this->getFacebookPixelCommandsInlineJavascript()
			);
		}

		return $javascript;
	}

	// }}}
	// {{{ public function getFacebookPixelTrackerInlineJavascript()

	public function getFacebookPixelTrackerInlineJavascript()
	{
		$javascript = null;

		if ($this->hasFacebookPixel()) {
			$javascript = <<<JS
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','//connect.facebook.net/en_US/fbevents.js');
JS;
		}

		return $javascript;
	}

	// }}}
	// {{{ public function getFacebookPixelCommandsInlineJavascript()

	public function getFacebookPixelCommandsInlineJavascript()
	{
		$javascript = null;

		if ($this->hasFacebookPixel() &&
			count($this->facebook_pixel_commands)) {
			$commands = null;

			// Always init with the account and track the pageview before any
			// further commands.
			$commands.= $this->getFacebookPixelCommand(
				array(
					'init',
					$this->facebook_pixel_id,
				)
			);

			foreach ($this->facebook_pixel_commands as $command) {
				$commands.= $this->getFacebookPixelCommand($command);
			}

			$javascript = <<<JS
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
	// {{{ protected function getGoogleAnalyticsTrackingCodeSource()

	protected function getGoogleAnalyticsTrackingCodeSource()
	{
		if ($this->display_advertising) {
			$source = ($this->app->isSecure())
				? 'https://stats.g.doubleclick.net/dc.js'
				: 'http://stats.g.doubleclick.net/dc.js';
		} else {
			$source = ($this->app->isSecure())
				? 'https://ssl.google-analytics.com/ga.js'
				: 'http://www.google-analytics.com/ga.js';
		}

		return $source;
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
	// {{{ protected function initFacebookPixelCommands()

	protected function initFacebookPixelCommands()
	{
		// Default commands for all sites:
		// * Speed sampling 100% of the time.
		// * Track the page view.
		$this->ga_commands = array(
			'PageView',
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
				$quoted_part = (is_float($part) || is_int($part))
					? $part
					: SwatString::quoteJavaScriptString($part);

				$options.= sprintf(
					', %s',
					$quoted_part
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
	// {{{ protected function getFacebookPixelCommand()

	protected function getFacebookPixelCommand($command)
	{
		$method  = null;
		$options = null;

		if (is_array($command)) {
			$method = array_shift($command);

			foreach ($command as $part) {
				$quoted_part = (is_float($part) || is_int($part))
					? $part
					: SwatString::quoteJavaScriptString($part);

				$options.= sprintf(
					', %s',
					$quoted_part
				);
			}
		} else {
			$method = $command;
		}

		return sprintf(
			'fbq(%s, %s%s);',
			SwatString::quoteJavaScriptString('track'),
			SwatString::quoteJavaScriptString($method),
			$options
		);
	}

	// }}}
}

?>
