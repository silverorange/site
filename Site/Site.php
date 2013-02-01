<?php

require_once 'Swat/Swat.php';
require_once 'Swat/SwatUI.php';
require_once 'Site/SiteError.php';
require_once 'Site/exceptions/SiteException.php';

/**
 * Container for package wide static methods
 *
 * @package   Site
 * @copyright 2005-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Site
{
	// {{{ constants

	/**
	 * The package identifier
	 */
	const PACKAGE_ID = 'Site';

	/**
	 * The gettext domain for Site
	 *
	 * This is used to support multiple locales.
	 */
	const GETTEXT_DOMAIN = 'site';

	// }}}
	// {{{ public static function _()

	/**
	 * Translates a phrase
	 *
	 * This is an alias for {@link Site::gettext()}.
	 *
	 * @param string $message the phrase to be translated.
	 *
	 * @return string the translated phrase.
	 */
	public static function _($message)
	{
		return Site::gettext($message);
	}

	// }}}
	// {{{ public static function gettext()

	/**
	 * Translates a phrase
	 *
	 * This method relies on the php gettext extension and uses dgettext()
	 * internally.
	 *
	 * @param string $message the phrase to be translated.
	 *
	 * @return string the translated phrase.
	 */
	public static function gettext($message)
	{
		return dgettext(Site::GETTEXT_DOMAIN, $message);
	}

	// }}}
	// {{{ public static function ngettext()

	/**
	 * Translates a plural phrase
	 *
	 * This method should be used when a phrase depends on a number. For
	 * example, use ngettext when translating a dynamic phrase like:
	 *
	 * - "There is 1 new item" for 1 item and
	 * - "There are 2 new items" for 2 or more items.
	 *
	 * This method relies on the php gettext extension and uses dngettext()
	 * internally.
	 *
	 * @param string $singular_message the message to use when the number the
	 *                                  phrase depends on is one.
	 * @param string $plural_message the message to use when the number the
	 *                                phrase depends on is more than one.
	 * @param integer $number the number the phrase depends on.
	 *
	 * @return string the translated phrase.
	 */
	public static function ngettext($singular_message, $plural_message, $number)
	{
		return dngettext(Site::GETTEXT_DOMAIN,
			$singular_message, $plural_message, $number);
	}

	// }}}
	// {{{ public static function setupGettext()

	public static function setupGettext()
	{
		$path = '@DATA-DIR@/Site/locale';
		if (substr($path, 0 ,1) === '@')
			$path = dirname(__FILE__).'/../locale';

		bindtextdomain(Site::GETTEXT_DOMAIN, $path);
		bind_textdomain_codeset(Site::GETTEXT_DOMAIN, 'UTF-8');
	}

	// }}}
	// {{{ public static function displayMethods()

	/**
	 * Displays the methods of an object
	 *
	 * This is useful for debugging.
	 *
	 * @param mixed $object the object whose methods are to be displayed.
	 */
	public static function displayMethods($object)
	{
		echo sprintf(Site::_('Methods for class %s:'), get_class($object));
		echo '<ul>';

		foreach (get_class_methods(get_class($object)) as $method_name)
			echo '<li>', $method_name, '</li>';

		echo '</ul>';
	}

	// }}}
	// {{{ public static function displayProperties()

	/**
	 * Displays the properties of an object
	 *
	 * This is useful for debugging.
	 *
	 * @param mixed $object the object whose properties are to be displayed.
	 */
	public static function displayProperties($object)
	{
		$class = get_class($object);

		echo sprintf(Site::_('Properties for class %s:'), $class);
		echo '<ul>';

		foreach (get_class_vars($class) as $property_name => $value) {
			$instance_value = $object->$property_name;
			echo '<li>', $property_name, ' = ', $instance_value, '</li>';
		}

		echo '</ul>';
	}

	// }}}
	// {{{ public static function getDependencies()

	/**
	 * Gets the packages this package depends on
	 *
	 * @return array an array of package IDs that this package depends on.
	 */
	public static function getDependencies()
	{
		$dependencies = array(Swat::PACKAGE_ID);

		if (class_exists('XML_RPCAjax'))
			$dependencies[] = XML_RPCAjax::PACKAGE_ID;

		return $dependencies;
	}

	// }}}
	// {{{ public static function getConfigDefinitions()

	/**
	 * Gets configuration definitions used by the Site package
	 *
	 * Applications should add these definitions to their config module before
	 * loading the application configuration.
	 *
	 * @return array the configuration definitions used by the Site package.
	 *
	 * @see SiteConfigModule::addDefinitions()
	 */
	public static function getConfigDefinitions()
	{
		return array(
			// Accounts
			// How long a persistent login cookie will exist in seconds.
			// Default value is 28 days.
			'account.persistent_login_time' => 2419200,

			// Whether or not persistent logins are enabled.
			'account.persistent_login_enabled' => false,

			// Whether or not to set a cookie containing the account id
			// for displaying a restore session message (i.e Welcome back
			// Joe. _Login_ to restore your 15 cart items.)
			'account.restore_cookie_enabled' => false,

			// Meta description for HTML head
			'site.meta_description'    => null,

			// Title of the site
			'site.title'               => null,

			// Resource tag, used for uncaching html-head-entries. Deprecated
			// in favor of 'resources.tag'.
			'site.resource_tag'        => null,

			// Resource tag, used for uncaching html-head-entries.
			'resources.tag'            => null,

			// Whether or not to combine resources
			'resources.combine'        => false,

			// Whether or not to minify resources
			'resources.minify'         => false,

			// Whether or not to use compiled resources
			'resources.compile'        => false,

			// DSN of database
			'database.dsn'             => null,

			// Default locale (defaults to 'en_CA.UTF8')
			'i18n.locale'              => 'en_CA.UTF8',

			// Default timezone
			'date.time_zone'           => null,

			// Salts
			'swat.form_salt'           => null,
			'cookies.salt'             => null,

			// URIs
			'uri.base'                 => null,
			'uri.secure_base'          => null,
			'uri.absolute_base'        => null,
			'uri.cdn_base'             => null,
			'uri.secure_cdn_base'      => null,
			'uri.admin_base'           => null,

			// Exceptions & errors
			'exceptions.log_location'  => null,
			'exceptions.base_uri'      => null,
			'exceptions.unix_group'    => null,
			'errors.log_location'      => null,
			'errors.base_uri'          => null,
			'errors.unix_group'        => null,
			'errors.fatal_severity'    => null,

			// Analytics
			// Google analytics website property id (UA-XXXXX-XXX)
			'analytics.google_account'    => null,
			// Google analytics account id (XXXXXXXX)
			'analytics.google_account_id' => null,

			// Ads
			// Tracking id in URIs
			'ads.tracking_id'          => 'utm_source',
			'ads.save_referer'         => '1',

			// Session
			'session.name'             => 'sessionid',
			'session.path'             => null,

			// Instance
			'instance.default'         => null,

			// Email
			'email.smtp_server'        => null,
			'email.log'                => true,

			// to address for contact-us emails
			'email.contact_address'    => null,

			// CC and BCC lists for contact-us emails
			// addresses are delimited by ; characters
			'email.contact_cc_list'    => null,
			'email.contact_bcc_list'   => null,

			// from address for contact-us emails (from "the website" to client)
			'email.website_address'    => null,

			// from address for automated emails sent by the system
			'email.service_address'    => null,

			// memcache
			'memcache.enabled'             => true,
			'memcache.server'              => 'localhost',
			'memcache.app_ns'              => '',
			'memcache.page_cache'          => false,
			'memcache.page_cache_timeout'  => 900, // in seconds
			'memcache.resource_cache'      => true,
			'memcache.resource_cache_stat' => true,

			// comments
			'comment.akismet_key'      => null,

			// amazon S3
			'amazon.bucket'            => null,
			'amazon.access_key_id'     => null,
			'amazon.access_key_secret' => null,

			// mobile
			'mobile.auto_relocate'        => false,

			// media
			'media.days_to_delete_threshold' => 7, // in days

			// Botr Media
			'botr.key'                    => null,
			'botr.secret'                 => null,
			'botr.base'                   => 'http://content.bitsontherun.com/',
			'botr.secure_base'            => 'https://content.bitsontherun.com/',
			'botr.content_signing'        => true,
			'botr.public_content_expiry'  => '+1 Year',
			'botr.private_content_expiry' => '+1 Minute',
			'botr.dashboard_username'     => null,
			'botr.dashboard_password'     => null,

			// Expiry dates for the privateer data deleter
			'expiry.contact_messages' => '1 year',

			// P3P headers. See http://en.wikipedia.org/wiki/P3P
			'p3p.compact_policy' => null,
			'p3p.policy_uri'     => null,

			// Sending notifications using Net_Notifier
			'notifier.address' => null,
			'notifier.site'    => null, // site identifier
			'notifier.timeout' => 200, // in milliseconds
		);
	}

	// }}}
}

// {{{ dummy dngettext()

/*
 * Define a dummy dngettext() for when gettext is not available.
 */
if (!function_exists("dngettext")) {
	function dngettext($domain, $messageid1, $messageid2, $n)
	{
		if ($n == 1)
			return $messageid1;

		return $messageid2;
    }
}

// }}}
// {{{ dummy dgettext()

/*
 * Define a dummy dgettext() for when gettext is not available.
 */
if (!function_exists("dgettext")) {
	function dgettext($domain, $messageid)
	{
		return $messageid;
	}
}

// }}}

Site::setupGettext();
SwatUI::mapClassPrefixToPath('Site', 'Site');

/*
 * Setup custom exception and error handlers.
 */
SiteError::setupHandler();
SiteException::setupHandler();

?>
