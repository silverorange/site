<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for handling site analytics.
 *
 * Currently only has support for Google Analytics, but other analytic trackers
 * could be added.
 *
 * @package   Site
 * @copyright 2007-2011 silverorange
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
	 * Slot reserved for the opt out custom variable.
	 */
	const CUSTOM_VARIABLE_OPT_OUT_SLOT = 5;

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
		$this->initGoogleAnalyticsCommands();
		$this->initOptOut();
	}

	// }}}
	// {{{ public function setGoogleAccount()

	public function setGoogleAccount($account)
	{
		$this->google_account = $account;
	}

	// }}}
	// {{{ public function hasGoogleAccount()

	public function hasGoogleAccount()
	{
		return ($this->google_account != '');
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

		if ($this->hasGoogleAccount() && count($this->ga_commands)) {
			$javascript = sprintf("%s\n%s",
				$this->getGoogleAnalyticsCommandsInlineJavascript(),
				$this->getGoogleAnalyticsTrackerInlineJavascript());
		}

		return $javascript;
	}

	// }}}
	// {{{ public function getGoogleAnalyticsCommandsInlineJavascript()

	public function getGoogleAnalyticsCommandsInlineJavascript()
	{
		$javascript = null;

		if ($this->hasGoogleAccount() && count($this->ga_commands)) {
			// always set the account first.
			$commands = $this->getGoogleAnalyticsCommand(array(
				'_setAccount',
				$this->google_account));

			foreach ($this->ga_commands as $command) {
				$commands.= $this->getGoogleAnalyticsCommand($command);
			}

			$javascript = <<<JS
var _gaq = _gaq || [];
%s
JS;

			$javascript = sprintf($javascript,
				$commands);
		}

		return $javascript;
	}

	// }}}
	// {{{ public function getGoogleAnalyticsTrackerInlineJavascript()

	public function getGoogleAnalyticsTrackerInlineJavascript()
	{
		$javascript = null;

		if ($this->hasGoogleAccount()) {
			$src = ($this->app->isSecure()) ?
				'https://ssl.google-analytics.com/ga.js' :
				'http://www.google-analytics.com/ga.js';

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

			$javascript = sprintf($javascript,
				$src);
		}

		return $javascript;
	}

	// }}}
	// {{{ protected function initGoogleAnalyticsCommands()

	protected function initGoogleAnalyticsCommands()
	{
		$this->ga_commands = array(
			'_trackPageview',
		);
	}

	// }}}
	// {{{ protected function initOptOut()

	protected function initOptOut()
	{
		if (isset($_GET['AnalyticsOptOut'])) {
			$ga_command = array(
				'_setCustomVar',
				self::CUSTOM_VARIABLE_OPT_OUT_SLOT,
				'AnalyticsOptOut',
				1,
				self::CUSTOM_VARIABLE_SCOPE_VISITOR,
				);

			$this->prependGoogleAnalyticsCommand($ga_command);
		}
	}

	// }}}
	// {{{ protected function getGoogleAnalyticsCommand()

	protected function getGoogleAnalyticsCommand($command)
	{
		$function = null;
		$options  = null;

		if (is_array($command)) {
			$function = array_shift($command);

			if (count($command)) {
				foreach ($command as $part) {
					$options.= sprintf(', %s',
						SwatString::quoteJavaScriptString($part));
				}
			}
		} else {
			$function = $command;
		}

		return sprintf("_gaq.push([%s%s]);",
			SwatString::quoteJavaScriptString($function),
			$options);
	}

	// }}}
}

?>
