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
     * Google Analytics 4 Account.
     */
    protected ?string $google4_account = null;

    /**
     * Google Tag Manager Account.
     */
    protected ?string $google_tag_manager_account = null;

    /**
     * Stack of commands to send to google tag manager.
     *
     * Each entry is an array where the first value is the google tag manager command,
     * and any following values are optional command parameters.
     *
     * @var array<int, string>
     */
    protected $google_tag_manager_commands = [];

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
     * Stack of commands to send to google analytics 4.
     *
     * Each entry is an array where the first value is the google analytics
     * command, and any following values are optional command parameters.
     *
     * @var array<int, string>
     */
    protected array $ga4_commands = [];

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
     * @var array<int, string>
     */
    protected $facebook_pixel_commands = [];

    public function init()
    {
        $config = $this->app->getModule('SiteConfigModule');

        $this->display_advertising =
            $config->analytics->google_display_advertising;

        $this->google4_account = $config->analytics->google4_account;

        $this->google_tag_manager_account = $config->analytics->google_tag_manager_account;

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
        $hasGoogleAnalytics4 = $this->hasGoogleAnalytics4();
        $hasGoogleTagManager = $this->hasGoogleTagManager();
        if ($hasGoogleAnalytics4 && $hasGoogleTagManager) {
            throw new Exception('Google Analytics 4 (GA4) and Google Tag Manager (GTM) are both active in config ini. It is best practice is to configure GA4 in GTM, not implement them both.');
        }

        return
            $hasGoogleAnalytics4
            || $hasGoogleTagManager
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

        if ($this->hasGoogleAnalytics4()) {
            $js .= $this->getGoogleAnalytics4InlineJavascript();
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

    public function hasGoogleAnalytics4(): bool
    {
        if ($this->hasGoogleTagManager()) {
            /**
             * If GTM is active then don't use GA4 and continue without alert.
             * There's an issue where on production the AOA site is treating
             * hasGoogleAnaltyics4() as true even though google4_account in production
             * ini config is not set. On stage there's no such unexcepted behaviour
             * (the cause of the issue on prod is not discovered yet).
             */
            return false;
        }

        return
            $this->google4_account != ''
            && !$this->analytics_opt_out
            && $this->analytics_enabled;
    }

    public function hasGoogleTagManager(): bool
    {
        return
            $this->google_tag_manager_account != ''
            && !$this->analytics_opt_out
            && $this->analytics_enabled;
    }

    public function getGoogleAnalytics4InlineJavascript(): ?string
    {
        $javascript = null;

        if ($this->hasGoogleAnalytics4()) {
            // Script head insert
            $javascript = $this->getGoogleAnalytics4TrackerInlineJavascript();
            $javascript .= "\n";

            // Default API config call and any commands
            $javascript .= $this->getGoogleAnalytics4CommandsInlineJavascript();
        }

        return $javascript;
    }

    public function getGoogleAnalytics4CommandsInlineJavascript(): string
    {
        $commands = '';

        // Event commands
        foreach ($this->ga4_commands as $command) {
            $commands .= $this->getGoogleAnalytics4CommandEvent(
                $command['event'],
                $command['event_params']
            );
        }

        $javascript = <<<'JS'
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', %s);
            %s
            JS;

        return sprintf(
            $javascript,
            SwatString::quoteJavaScriptString($this->google4_account),
            $commands
        );
    }

    public function getGoogleAnalytics4TrackerInlineJavascript(): ?string
    {
        $javascript = null;

        if ($this->hasGoogleAnalytics4()) {
            $javascript = <<<'JS'
                (function() {
                    var ga = document.createElement('script');
                    ga.type = 'text/javascript';
                    ga.async = true;
                    ga.src = %s;
                    var s = document.getElementsByTagName('script')[0];
                    s.parentNode.insertBefore(ga, s);
                })();
                JS;

            $javascript = sprintf(
                $javascript,
                SwatString::quoteJavaScriptString(
                    $this->getGoogleAnalytics4TrackingCodeSource(
                        $this->google4_account
                    )
                )
            );
        }

        return $javascript;
    }

    protected function getGoogleAnalytics4TrackingCodeSource(string $id)
    {
        return "https://www.googletagmanager.com/gtag/js?id={$id}";
    }

    public function getGoogleTagManagerTrackerInlineJavascript(): ?string
    {
        if (!$this->hasGoogleTagManager()) {
            return null;
        }

        return sprintf(
            <<<'JS'
                (function(w, d, s, l, i){
                    w[l] = w[l] || [];
                    w[l].push({'gtm.start': new Date().getTime(), event:'gtm.js'});
                    var f = d.getElementsByTagName(s)[0],
                        j = d.createElement(s),
                        dl = l != 'dataLayer' ? '&l=' + l : '';
                        j.async = true;
                        j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                        f.parentNode.insertBefore(j, f);
                })(window, document, 'script', 'dataLayer', %s);
                JS,
            SwatString::quoteJavaScriptString(
                $this->google_tag_manager_account,
            ),
        );
    }

    public function pushGoogleAnalytics4Commands(array $commands): void
    {
        foreach ($commands as $command) {
            $this->ga4_commands[] = $command;
        }
    }

    /**
     * @param list<string> $commands are data structures destined to be a JSON parameter for dataLayer.push()
     */
    public function pushGoogleTagManagerCommands(array $commands): void
    {
        $ecommerce_cleared = false;
        foreach ($commands as $command) {
            if (isset($command['ecommerce']) && !$ecommerce_cleared) {
                // An ecommerce data structure - send a clear object command
                // per GTM documentation
                $this->google_tag_manager_commands[] = ['ecommerce' => null];
                $ecommerce_cleared = true;
            }
            $this->google_tag_manager_commands[] = $command;
        }
    }

    public function getGoogleTagManagerInlineJavascript(): ?string
    {
        if (!$this->hasGoogleTagManager()) {
            return null;
        }

        // SEE https://developers.google.com/tag-platform/tag-manager/datalayer#persist_data_layer_variables
        // Call and any commands via dataLayer (which must happen before GTM snippet is initiated),
        // then init GTM snippet (script head insert)
        return ($this->getGoogleTagManagerCommandsInlineJavascript() ?? '') .
            "\n" .
            ($this->getGoogleTagManagerTrackerInlineJavascript() ?? '');
    }

    public function getGoogleTagManagerCommandsInlineJavascript(): ?string
    {
        if (!$this->hasGoogleTagManager()) {
            return null;
        }

        // Event commands
        $pushes = [];
        foreach ($this->google_tag_manager_commands as $command) {
            $pushes[] = sprintf(
                <<<'JS'
                    dataLayer.push(%s);
                    JS,
                json_encode($command),
            );
        }

        return sprintf(
            <<<'JS'
                window.dataLayer = window.dataLayer || [];
                %s
                JS,
            implode("\n", $pushes),
        );
    }

    protected function getGoogleAnalytics4CommandEvent(
        string $event_name,
        array $event_params
    ): string {
        return sprintf(
            'gtag(\'event\', %s, %s);',
            SwatString::quoteJavaScriptString($event_name),
            json_encode($event_params)
        );
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
