<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for handling site analytics.
 *
 * Currently only has support for Google Analytics, Facebook Pixels and
 * Bing Universal Event Tracking.
 *
 * @package   Site
 * @copyright 2007-2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link      http://code.google.com/apis/analytics/docs/tracking/asyncTracking.html
 * @link      https://developers.facebook.com/docs/facebook-pixel/api-reference
 * @link      http://help.bingads.microsoft.com/apex/index/3/en-ca/n5012
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

	/**
	 * Bing UET Account
	 *
	 * @var string
	 */
	protected $bing_uet_id;

	/**
	 * Stack of commands to send to bing UET
	 *
	 * Each entry is an array where the first value is the bing UET
	 * command, and any following values are optional command parameters.
	 *
	 * @var array
	 */
	protected $bing_uet_commands = array();

	/**
	 * Twitter Pixel User-Tracking Tag
	 *
	 * @var string
	 */
	protected $twitter_track_pixel_id;

	/**
	 * Twitter Pixel Purchase Tag
	 *
	 * @var string
	 */
	protected $twitter_purchase_pixel_id;

	/**
	 * Stack of commands to send to twitter pixels
	 *
	 * Commands are key-value pairs.
	 *
	 * @var array
	 */
	protected $twitter_pixel_commands = array();

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
		$this->bing_uet_id = $config->analytics->bing_uet_id;

		$this->twitter_track_pixel_id =
			$config->analytics->twitter_track_pixel_id;

		$this->twitter_purchase_pixel_id =
			$config->analytics->twitter_purchase_pixel_id;

		$this->initOptOut();

		// skip init of the commands if we're opted out.
		if (!$this->analytics_opt_out) {
			$this->initGoogleAnalyticsCommands();
			$this->initFacebookPixelCommands();
			$this->initBingUETCommands();
		}
	}

	// }}}
	// {{{ public function hasAnalytics()

	public function hasAnalytics()
	{
		return (
			$this->hasGoogleAnalytics() ||
			$this->hasFacebookPixel() ||
			$this->hasTwitterPixel() ||
			$this->hasBingUET()
		);
	}

	// }}}
	// {{{ public function displayAnalytics()

	public function displayAnalytics()
	{
		$this->displayFacebookPixelImage();
		$this->displayTwitterPixelImages();
		$this->displayBingUETImage();
	}

	// }}}
	// {{{ public function getInlineJavaScript()

	public function getInlineJavaScript()
	{
		$js = '';

		if ($this->hasFacebookPixel()) {
			$js.= $this->getFacebookPixelInlineJavascript();
		}

		if ($this->hasBingUET()) {
			$js.= $this->getBingUETInlineJavascript();
		}

		if ($this->hasGoogleAnalytics()) {
			$js.= $this->getGoogleAnalyticsInlineJavascript();
		}

		if ($this->hasTwitterPixel()) {
			$js.= $this->getTwitterPixelInlineJavascript();
		}

		return $js;
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
			if (!$cookie_module instanceof SiteCookieModule) {
				$e = new SiteException(
					'Attempting to remove Analytics Opt '.
					'Out Cookie with no SiteCookieModule available.'
				);

				$e->processAndContinue();
			} else {
				$cookie_module->removeCookie('AnalyticsOptOut');
			}
		}

		// Opt Out trumps opt in if you include them both flags in your query
		// string for some reason.
		if (isset($_GET['AnalyticsOptOut'])) {
			$this->analytics_opt_out = true;
			if (!$cookie_module instanceof SiteCookieModule) {
				$e = new SiteException(
					'Attempting to set Analytics Opt Out '.
					'Cookie with no SiteCookieModule available.'
				);

				$e->processAndContinue();
			} else {
				// 10 years should be equivalent to never expiring.
				$cookie_module->setCookie(
					'AnalyticsOptOut',
					'1',
					strtotime('+10 years')
				);
			}
		}
	}

	// }}}

	// Google Analytics
	// {{{ public function hasGoogleAnalytics()

	public function hasGoogleAnalytics()
	{
		return (
			$this->google_account != '' &&
			!$this->analytics_opt_out
		);
	}

	// }}}
	// {{{ public function pushGoogleAnalyticsCommands()

	public function pushGoogleAnalyticsCommands(array $commands)
	{
		foreach ($commands as $command) {
			$this->ga_commands[] = $command;
		}
	}

	// }}}
	// {{{ public function prependGoogleAnalyticsCommands()

	public function prependGoogleAnalyticsCommands(array $commands)
	{
		$comands = array_reverse($commands);
		foreach ($commands as $command) {
			array_unshift($this->ga_commands, $command);
		}
	}

	// }}}
	// {{{ public function getGoogleAnalyticsInlineJavascript()

	public function getGoogleAnalyticsInlineJavascript()
	{
		$javascript = null;

		if ($this->hasGoogleAnalytics() && count($this->ga_commands) > 0) {
			$javascript = $this->getGoogleAnalyticsCommandsInlineJavascript();
			$javascript.= "\n";
			$javascript.= $this->getGoogleAnalyticsTrackerInlineJavascript();
		}

		return $javascript;
	}

	// }}}
	// {{{ public function getGoogleAnalyticsCommandsInlineJavascript()

	public function getGoogleAnalyticsCommandsInlineJavascript()
	{
		$javascript = null;

		if ($this->hasGoogleAnalytics() && count($this->ga_commands) > 0) {
			$commands = '';

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

			$javascript = <<<'JS'
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
			$javascript = <<<'JS'
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
	// {{{ protected function getGoogleAnalyticsCommand()

	protected function getGoogleAnalyticsCommand($command)
	{
		$method  = '';
		$options = '';

		if (is_array($command)) {
			$method = array_shift($command);

			foreach ($command as $part) {
				$quoted_part = (is_float($part) || is_int($part))
					? $part
					: SwatString::quoteJavaScriptString($part);

				$options.= ', '.$quoted_part;
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

	// Facebook
	// {{{ public function hasFacebookPixel()

	public function hasFacebookPixel()
	{
		return (
			$this->facebook_pixel_id != '' &&
			!$this->analytics_opt_out
		);
	}

	// }}}
	// {{{ public function pushFacebookPixelCommands()

	public function pushFacebookPixelCommands(array $commands)
	{
		foreach ($commands as $command) {
			$this->facebook_pixel_commands[] = $command;
		}
	}

	// }}}
	// {{{ public function prependFacebookPixelCommands()

	public function prependFacebookPixelCommands(array $commands)
	{
		$comands = array_reverse($commands);
		foreach ($commands as $command) {
			array_unshift($this->facebook_pixel_commands, $command);
		}
	}

	// }}}
	// {{{ public function getFacebookPixelImage()

	public function getFacebookPixelImage()
	{
		$xhtml = <<<'XHTML'
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=%s&ev=PageView&noscript=1"/></noscript>
XHTML;

		return sprintf(
			$xhtml,
			SwatString::minimizeEntities(rawurlencode($this->facebook_pixel_id))
		);
	}

	// }}}
	// {{{ public function getFacebookPixelInlineJavascript()

	public function getFacebookPixelInlineJavascript()
	{
		$javascript = null;

		if ($this->hasFacebookPixel() &&
			count($this->facebook_pixel_commands) > 0) {
			$javascript = $this->getFacebookPixelTrackerInlineJavascript();
			$javascript.= "\n";
			$javascript.= $this->getFacebookPixelCommandsInlineJavascript();
		}

		return $javascript;
	}

	// }}}
	// {{{ public function getFacebookPixelTrackerInlineJavascript()

	public function getFacebookPixelTrackerInlineJavascript()
	{
		$javascript = null;

		if ($this->hasFacebookPixel()) {
			$javascript = <<<'JS'
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
			count($this->facebook_pixel_commands) > 0) {
			// Always init with the account and track the pageview before any
			// further commands.
			$javascript = $this->getFacebookPixelCommand(
				array(
					'init',
					$this->facebook_pixel_id,
				)
			);

			foreach ($this->facebook_pixel_commands as $command) {
				$javascript.= $this->getFacebookPixelCommand($command);
			}
		}

		return $javascript;
	}

	// }}}
	// {{{ protected function initFacebookPixelCommands()

	protected function initFacebookPixelCommands()
	{
		// Default commands for all sites:
		// * Track the page view.
		$this->facebook_pixel_commands = array(
			array(
				'track',
				'PageView',
			),
		);
	}

	// }}}
	// {{{ protected function displayFacebookPixelImage()

	protected function displayFacebookPixelImage()
	{
		if ($this->hasFacebookPixel()) {
			$image = $this->getFacebookPixelImage();
			if ($image != '') {
				echo $image;
			}
		}
	}

	// }}}
	// {{{ protected function getFacebookPixelCommand()

	protected function getFacebookPixelCommand($command)
	{
		if (!is_array($command)) {
			$command = array($command);
		}

		return sprintf(
			'fbq(%s);',
			implode(', ', array_map('json_encode', $command))
		);
	}

	// }}}

	// Twitter
	// {{{ public function hasTwitterPixel()

	public function hasTwitterPixel()
	{
		return (
			$this->twitter_track_pixel_id != '' &&
			!$this->analytics_opt_out
		);
	}

	// }}}
	// {{{ public function pushTwitterPixelCommands()

	public function pushTwitterPixelCommands(array $commands)
	{
		foreach ($commands as $name => $value) {
			$this->twitter_pixel_commands[$name] = $value;
		}
	}

	// }}}
	// {{{ public function getTwitterPixelImages()

	public function getTwitterPixelImages()
	{
		$xhtml = <<<'XHTML'
<noscript>
<img height="1" width="1" style="display:none;" alt="" src="https://analytics.twitter.com/i/adsct?txn_id=%1$s&amp;p_id=Twitter" />
<img height="1" width="1" style="display:none;" alt="" src="//t.co/i/adsct?txn_id=%1$s&amp;p_id=Twitter" />
<img height="1" width="1" style="display:none;" alt="" src="https://analytics.twitter.com/i/adsct?txn_id=%2$s&amp;p_id=Twitter&%3$s" />
<img height="1" width="1" style="display:none;" alt="" src="//t.co/i/adsct?txn_id=%2$s&amp;p_id=Twitter&amp;%3$s" />
</noscript>
XHTML;

		$track_pixel = rawurlencode($this->twitter_track_pixel_id);
		$purchase_pixel = rawurlencode($this->twitter_purchase_pixel_id);

		$query_vars = array();
		foreach ($this->twitter_pixel_commands as $name => $value) {
			$query_vars[$name] = sprintf(
				'%s=%s',
				SwatString::minimizeEntities(rawurlencode($name)),
				SwatString::minimizeEntities(rawurlencode($value))
			);
		}

		return sprintf(
			$xhtml,
			SwatString::minimizeEntities($track_pixel),
			SwatString::minimizeEntities($purchase_pixel),
			implode('&amp;', $query_vars)
		);
	}

	// }}}
	// {{{ public function getTwitterPixelInlineJavascript()

	public function getTwitterPixelInlineJavascript()
	{
		$javascript = '';

		if ($this->hasTwitterPixel()) {
			$javascript = $this->getTwitterPixelTrackerInlineJavascript();
		}

		return $javascript;
	}

	// }}}
	// {{{ public function getTwitterPixelTrackerInlineJavascript()

	public function getTwitterPixelTrackerInlineJavascript()
	{
		$javascript = <<<'JS'
(function() {
var twitter_script = document.createElement('script');
twitter_script.type = 'text/javascript';
twitter_script.src = '//platform.twitter.com/oct.js';

var onload = function() {
	twttr.conversion.trackPid(%s);
	twttr.conversion.trackPid(%s, %s);
};

if (typeof document.attachEvent === 'object') {
	// Support IE8
	twitter_script.onreadystatechange = function() {
		if (twitter_script.readyState === 'loaded') {
			onload();
		}
	};
} else {
	twitter_script.onload = onload;
}

var s = document.getElementsByTagName('script')[0];
s.parentNode.insertBefore(twitter_script, s);
})();
JS;

		return sprintf(
			$javascript,
			SwatString::quoteJavaScriptString($this->twitter_track_pixel_id),
			SwatString::quoteJavaScriptString($this->twitter_purchase_pixel_id),
			json_encode($this->twitter_pixel_commands)
		);
	}

	// }}}
	// {{{ protected function displayTwitterPixelImages()

	protected function displayTwitterPixelImages()
	{
		if ($this->hasTwitterPixel()) {
			$images = $this->getTwitterPixelImages();
			if ($images != '') {
				echo $images;
			}
		}
	}

	// }}}

	// Bing
	// {{{ public function hasBingUET()

	public function hasBingUET()
	{
		return (
			$this->bing_uet_id != '' &&
			!$this->analytics_opt_out
		);
	}

	// }}}
	// {{{ public function pushBingUETCommands()

	public function pushBingUETCommands(array $commands)
	{
		foreach ($commands as $command) {
			$this->bing_uet_commands[] = $command;
		}
	}

	// }}}
	// {{{ public function prependBingUETCommands()

	public function prependBingUETCommands(array $commands)
	{
		$comands = array_reverse($commands);
		foreach ($commands as $command) {
			array_unshift($this->bing_uet_commands, $command);
		}
	}

	// }}}
	// {{{ public function getBingUETImage()

	public function getBingUETImage()
	{
		$xhtml = <<<'XHTML'
<noscript><img src="//bat.bing.com/action/0?ti=%s&Ver=2" height="0" width="0" style="display:none; visibility: hidden;" /></noscript>
XHTML;

		return sprintf(
			$xhtml,
			SwatString::minimizeEntities(rawurlencode($this->bing_uet_id))
		);
	}

	// }}}
	// {{{ public function getBingUETInlineJavascript()

	public function getBingUETInlineJavascript()
	{
		$javascript = null;

		// Bing UET doens't have an init command, and the initial tracker setup
		// happens as part of the code in
		// SiteAnalyticsModule::getBingUETTrackerInlineJavascript().
		// This is different that the other trackers in SiteAnalyticsModule.
		if ($this->hasBingUET()) {
			$javascript = $this->getBingUETTrackerInlineJavascript();
			if (count($this->bing_uet_commands) > 0) {
				$javascript.= "\n";
				$javascript.= $this->getBingUETCommandsInlineJavascript();
			}
		}

		return $javascript;
	}

	// }}}
	// {{{ public function getBingUETTrackerInlineJavascript()

	public function getBingUETTrackerInlineJavascript()
	{
		$javascript = null;

		if ($this->hasBingUET()) {
			$javascript = <<<'JS'
(function(w,d,t,r,u){var f,n,i;w[u]=w[u]||[],f=function(){var o={ti:"%s"};o.q=w[u],w[u]=new UET(o),w[u].push("pageLoad")},n=d.createElement(t),n.src=r,n.async=1,n.onload=n.onreadystatechange=function(){var s=this.readyState;s&&s!=="loaded"&&s!=="complete"||(f(),n.onload=n.onreadystatechange=null)},i=d.getElementsByTagName(t)[0],i.parentNode.insertBefore(n,i)})(window,document,"script","//bat.bing.com/bat.js","uetq");
window.uetq = window.uetq || [];
JS;
		}

		return sprintf(
			$javascript,
			SwatString::quoteJavaScriptString($this->bing_uet_id)
		);
	}

	// }}}
	// {{{ public function getBingUETCommandsInlineJavascript()

	public function getBingUETCommandsInlineJavascript()
	{
		$javascript = null;

		if ($this->hasBingUET() &&
			count($this->bing_uet_commands) > 0) {
			foreach ($this->bing_uet_commands as $command) {
				$javascript.= $this->getBingUETCommand($command);
			}
		}

		return $javascript;
	}

	// }}}
	// {{{ protected function initBingUETCommands()

	protected function initBingUETCommands()
	{
		// No default commands to init, as the basic track page view happens
		// in the tracker setup javascript in
		// SiteAnalyticsModule::getBingUETTrackerInlineJavascript().
	}

	// }}}
	// {{{ protected function getBingUETCommand()

	protected function getBingUETCommand($command)
	{
		if (!is_array($command)) {
			$command = array($command);
		}

		return sprintf(
			'window.uetq.push(%s);',
			json_encode($command)
		);
	}

	// }}}
	// {{{ protected function displayBingUETImage()

	protected function displayBingUETImage()
	{
		if ($this->hasBingUET()) {
			$image = $this->getBingUETImage();
			if ($image != '') {
				echo $image;
			}
		}
	}

	// }}}
}

?>
