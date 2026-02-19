<?php

/**
 * Web application module for handling site analytics.
 *
 * Currently has support for Google Tag Manager and Meta/Facebook Pixel Event Tracking.
 *
 * @copyright 2007-2026 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see      https://developers.google.com/tag-platform/tag-manager
 * @see      https://developers.facebook.com/docs/meta-pixel/
 */
class SiteAnalyticsModule extends SiteApplicationModule
{
    /**
     * Total number of available slots for custom variables.
     */
    public const CUSTOM_VARIABLE_SLOTS = 5;

    /**
     * Available scopes for custom variables.
     */
    public const CUSTOM_VARIABLE_SCOPE_VISITOR = 1;
    public const CUSTOM_VARIABLE_SCOPE_SESSION = 2;
    public const CUSTOM_VARIABLE_SCOPE_PAGE = 3;

    /**
     * Google Tag Manager Account.
     *
     * @var string
     */
    protected $google_tag_manager_account;

    /**
     * Flag to tell whether analytics are enabled on this site.
     *
     * @var bool
     */
    protected $analytics_enabled = true;

    /**
     * Flag to tell whether the user has opted out of analytics.
     *
     * @var bool
     */
    protected $analytics_opt_out = false;

    /**
     * Flag to tell whether to load the enchanced link attribution plugin.
     *
     * @var bool
     *
     * @see https://support.google.com/analytics/answer/2558867
     */
    protected $enhanced_link_attribution = false;

    /**
     * Flag to tell whether to use the display advertisor features.
     *
     * These are used for demographic and interest reports on GA, as well as
     * remarketing and Google Display Network impression reporting.
     *
     * @var bool
     *
     * @see https://support.google.com/analytics/answer/2444872
     */
    protected $display_advertising = false;

    /**
     * Facebook Pixel Account.
     *
     * @var string
     */
    protected $facebook_pixel_id;

    /**
     * Stack of commands to send to facebook pixels.
     *
     * Each entry is an array where the first value is the facebook pixel
     * command, and any following values are optional command parameters.
     *
     * @var array
     */
    protected $facebook_pixel_commands = [];

    public function init()
    {
        $config = $this->app->getModule('SiteConfigModule');

        $this->enhanced_link_attribution =
            $config->analytics->google_enhanced_link_attribution;

        $this->google_tag_manager_account = $config->analytics->google_tag_manager_account;

        $this->display_advertising =
            $config->analytics->google_display_advertising;

        $this->facebook_pixel_id = $config->analytics->facebook_pixel_id;

        if (!$config->analytics->enabled) {
            $this->analytics_enabled = false;
        }

        $this->initOptOut();

        // skip init of the commands if we're opted out.
        if (!$this->analytics_opt_out) {
            $this->initFacebookPixelCommands();
        }
    }

    public function hasAnalytics()
    {
        return
            $this->hasGoogleTagManager()
            || $this->hasFacebookPixel();
    }

    public function displayNoScriptContent()
    {
        $this->displayFacebookPixelImage();
    }

    public function displayScriptContent()
    {
        $js = '';

        if ($this->hasFacebookPixel()) {
            $js .= $this->getFacebookPixelInlineJavascript();
        }

        if ($this->hasGoogleTagManager()) {
            $js .= $this->getGoogleTagManagerInlineJavascript();
        }

        if ($js != '') {
            Swat::displayInlineJavaScript($js);
        }
    }

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
                    'Attempting to remove Analytics Opt ' .
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
                    'Attempting to set Analytics Opt Out ' .
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

    // Google
    
    public function hasGoogleTagManager(): bool
    {
        return
            $this->google_tag_manager_account != ''
            && !$this->analytics_opt_out
            && $this->analytics_enabled;
    }

    public function getGoogleTagManagerInlineJavascript(): ?string
    {
        $javascript = null;

        if ($this->hasGoogleTagManager()) {
            $javascript = <<<'JS'
                (function() {
                    var gtm = document.createElement('script');
                    gtm.type = 'text/javascript';
                    gtm.async = true;
                    gtm.src = %s;
                    var s = document.getElementsByTagName('script')[0];
                    s.parentNode.insertBefore(gtm, s);
                })();
                JS;

            $javascript = sprintf(
                $javascript,
                SwatString::quoteJavaScriptString(
                    $this->getGoogleTagManagerCodeSource(
                        $this->google_tag_manager_account
                    )
                )
            );
        }

        return $javascript;
    }

    protected function getGoogleTagManagerCodeSource(string $id)
    {
        return "https://www.googletagmanager.com/gtm.js?id={$id}";
    }


    // Facebook

    public function hasFacebookPixel()
    {
        return
            $this->facebook_pixel_id != ''
            && !$this->analytics_opt_out
            && $this->analytics_enabled;
    }

    public function pushFacebookPixelCommands(array $commands)
    {
        foreach ($commands as $command) {
            $this->facebook_pixel_commands[] = $command;
        }
    }

    public function prependFacebookPixelCommands(array $commands)
    {
        $comands = array_reverse($commands);
        foreach ($commands as $command) {
            array_unshift($this->facebook_pixel_commands, $command);
        }
    }

    public function getFacebookPixelImage()
    {
        // @codingStandardsIgnoreStart
        $xhtml = <<<'XHTML'
            <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=%s&ev=PageView&noscript=1"/></noscript>
            XHTML;

        // @codingStandardsIgnoreEnd
        return sprintf(
            $xhtml,
            SwatString::minimizeEntities(rawurlencode($this->facebook_pixel_id))
        );
    }

    public function getFacebookPixelInlineJavascript()
    {
        $javascript = null;

        if ($this->hasFacebookPixel()
            && count($this->facebook_pixel_commands) > 0) {
            $javascript = $this->getFacebookPixelTrackerInlineJavascript();
            $javascript .= "\n";
            $javascript .= $this->getFacebookPixelCommandsInlineJavascript();
        }

        return $javascript;
    }

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

    public function getFacebookPixelCommandsInlineJavascript()
    {
        $javascript = null;

        if ($this->hasFacebookPixel()
            && count($this->facebook_pixel_commands) > 0) {
            // Always init with the account and track the pageview before any
            // further commands.
            $javascript = $this->getFacebookPixelCommand(
                ['init', $this->facebook_pixel_id]
            );

            foreach ($this->facebook_pixel_commands as $command) {
                $javascript .= $this->getFacebookPixelCommand($command);
            }
        }

        return $javascript;
    }

    protected function initFacebookPixelCommands()
    {
        // Default commands for all sites:
        // * Track the page view.
        $this->facebook_pixel_commands = [['track', 'PageView']];
    }

    protected function displayFacebookPixelImage()
    {
        if ($this->hasFacebookPixel()) {
            $image = $this->getFacebookPixelImage();
            if ($image != '') {
                echo $image;
            }
        }
    }

    protected function getFacebookPixelCommand($command)
    {
        if (!is_array($command)) {
            $command = [$command];
        }

        return sprintf(
            'fbq(%s);',
            implode(', ', array_map('json_encode', $command))
        );
    }
}
