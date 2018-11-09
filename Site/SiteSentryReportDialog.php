<?php

/**
 * Generates Sentry Report Dialog for User Input
 *
 * @package   Store
 * @copyright 2018 silverorange
 */
class SiteSentryReportDialog
{
  	// {{{ protected properties

  	/**
     * The Sentry client
     *
     * @var Raven_Client
     */
  	protected $sentry_client;

    /**
     * @var string
     */
    protected $sentry_dsn;

  	// }}}
  	// {{{ public function __construct()

  	public function __construct($sentry_client, $sentry_dsn) {
  		$this->sentry_client = $sentry_client;
      $this->sentry_dsn = $sentry_dsn;
  	}

  	// }}}
  	// {{{ public function getInlineXhtml()

  	public function getInlineXhtml()
  	{
  		$event_id = SwatString::quoteJavaScriptString(
        $this->sentry_client->getLastEventID()
      );

      $sentry_dsn = SwatString::quoteJavaScriptString(
        $this->sentry_dsn
      );

  		$html = <<<HTML
<div class="sentry-report-dialog">
  <script src="https://cdn.ravenjs.com/2.3.0/raven.min.js"></script>

  <script>
    Raven.showReportDialog({
      eventId: %s,
      dsn: %s
    });
  </script>
</div>
HTML;

  		return sprintf(
  			$html,
  			$event_id,
  			$sentry_dsn
  		);
  	}

  	// }}}
  }

  ?>
