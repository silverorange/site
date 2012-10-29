<?php

require_once 'Swat/exceptions/SwatClassNotFoundException.php';
require_once 'Site/exceptions/SiteException.php';
require_once 'Site/SiteApplicationModule.php';
require_once 'Net/Notifier/Sender.php';

/**
 * Web application module for sending notifications
 *
 * @package   Site
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteNotifierModule extends SiteApplicationModule
{
	// {{{ public function init()

	public function init()
	{
		// no initialization required
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The notifier module depends on the SiteConfigModule feature.
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
	// {{{ public function send()

	public function send($action, array $data = array())
	{
		try {
			$config = $this->app->getModule('SiteConfigModule');

			if ($config->notifier->address != '') {
				$sender = new Net_Notifier_Sender(
					$config->notifier->address,
					$config->notifier->timeout
				);
				$sender->send($action, $data);
			}
		} catch (Net_Notifier_Exception $e) {
			// ignore notification errors
		}
	}

	// }}}
}

?>
