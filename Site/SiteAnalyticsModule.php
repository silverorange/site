<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/dataobjects/SiteAd.php';
require_once 'Site/dataobjects/SiteAdReferrer.php';

/**
 * Web application module for handling site analytics and ad tracking
 *
 * @package   Site
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAnalyticsModule extends SiteApplicationModule
{
	// {{{ protected properties

	/**
	 * The name of the inbound ad tracking URI parameter
	 *
	 * Defaults to 'ad'. Google Analytics users may want to change this to
	 * 'utm_source' or 'utm_campaign'.
	 *
	 * @var string
	 *
	 * @see SiteAnalyticsModule::setInboundTrackingId()
	 * @see SiteAnalyticsModule::cleanInboundTrackingUri()
	 */
	protected $inbound_tracking_id = 'ad';

	/**
	 * Whether or not to automaticlaly clean the inbound tracking id from URIs
	 *
	 * Defaults to false.
	 *
	 * @var boolean
	 *
	 * @see SiteAnalyticsModule::setAutocleanUri()
	 */
	protected $clean_inbound_tracking_uri = false;

	// }}}
	// {{{ public function init()

	public function init()
	{
		$this->initAd();
	}

	// }}}
	// {{{ public function getAd()

	/**
	 * Gets the tracked inbound ad for the current session
	 *
	 * @return SiteAd the tracked inbound ad for the current session or null
	 *                 if there is no tracked inbound ad for the current
	 *                 session.
	 */
	public function getAd()
	{
		$session = $this->app->getModule('SiteSessionModule');
		return (isset($session->ad)) ? $session->ad : null;
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The site analytics module depends on the SiteSessionModule and
	 * SiteDatabaseModule features.
	 *
	 * @return array an array of {@link SiteApplicationModuleDependency}
	 *                        objects defining the features this module
	 *                        depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();
		$depends[] = new SiteApplicationModuleDependency('SiteDatabaseModule');
		$depends[] = new SiteApplicationModuleDependency('SiteSessionModule');
		return $depends;
	}

	// }}}
	// {{{ public function cleanInboundTrackingUri()

	/**
	 * Removes inbound ad tracking id from the URI and relocates to a clean
	 * URI
	 *
	 * @param SiteAd $ad the inbound tracking ad to clean from the URI.
	 */
	public function cleanInboundTrackingUri(SiteAd $ad)
	{
		$regexp = sprintf('/&?\??%s=%s/u',
			preg_quote($this->inbound_tracking_id, '/'),
			preg_quote($ad->shortname, '/'));

		$uri = preg_replace($regexp, '', $_SERVER['REQUEST_URI']);
		$this->app->relocate($uri);
	}

	// }}}
	// {{{ public function setInboundTrackingId()

	/**
	 * Sets the name of the URI parameter used for tracking inbound ads
	 *
	 * @param string the name of the URI parameter used for tracking inbound
	 *                ads. Example: 'utm_source'.
	 */
	public function setInboundTrackingId($id)
	{
		$this->inbound_tracking_id = (string)$id;
	}

	// }}}
	// {{{ public function setAutocleanUri()

	/**
	 * Sets whether or not inbound tracking ids should be automatically cleaned
	 * from URIs
	 *
	 * @param boolean $clean optional. True if inbound tracking ids should be
	 *                        automatically cleaned from URIs and false if they
	 *                        should not. If set to true, the ad tracking id is
	 *                        removed from the URI and a relocate is performed.
	 */
	public function setAutocleanUri($clean = true)
	{
		$this->clean_inbound_tracking_uri = (boolean)$clean;
	}

	// }}}
	// {{{ protected function initAd()

	/**
	 * Reads inbound tracking ids from the request URI and saves a new ad
	 * referrer
	 *
	 * If autoclean URI is specified, after the referral is logged, the inbound
	 * tracking id is removed from the URI through a relocate.
	 */
	protected function initAd()
	{
		$db = $this->app->getModule('SiteDatabaseModule')->getConnection();

		$shortname = SiteApplication::initVar($this->inbound_tracking_id,
			SiteApplication::VAR_POST | SiteApplication::VAR_GET);

		$class_name = SwatDBClassMap::get('SiteAd');
		$ad = new $class_name();
		$ad->setDatabase($db);
		if ($ad->loadFromShortname($shortname)) {
			$session = $this->app->getModule('SiteSessionModule');
			if (!$session->isActive()) {
				$session->activate();
			}

			$session->ad = $ad;

			/*
			 * Due to mass mailings, large numbers of people follow links with
			 * ads which can lead to database deadlock when inserting the ad
			 * referrer. Here we make five attempts before giving up and
			 * throwing the exception.
			 */
			for ($attempt = 0; true; $attempt++) {
				try {
					$this->insertInboundTrackingReferrer($ad);
					break;
				} catch (SwatDBException $e) {
					if ($attempt > 5)
						throw $e;
				}
			}

			if ($this->clean_inbound_tracking_uri) {
				$this->cleanInboundTrackingUri($ad);
			}
		}
	}

	// }}}
	// {{{ protected function insertInboundTrackingReferrer()

	/**
	 * Inserts a referrer for an inbound tracking id
	 *
	 * This is used to cout the number of site referrals for a particular
	 * tracking id.
	 *
	 * @param SiteAd $ad the inbound ad to track.
	 */
	protected function insertInboundTrackingReferrer(SiteAd $ad)
	{
		$db = $this->app->getModule('SiteDatabaseModule')->getConnection();

		$class_name = SwatDBClassMap::get('SiteAdReferrer');
		$ad_referrer = new $class_name();

		$ad_referrer->setDatabase($db);
		$ad_referrer->createdate = new SwatDate();
		$ad_referrer->createdate->toUTC();
		$ad_referrer->ad = $ad;

		$ad_referrer->save();
	}

	// }}}
}

?>
