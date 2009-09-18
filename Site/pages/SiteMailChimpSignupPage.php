<?php

require_once 'Site/pages/SiteMailingSignupPage.php';
require_once 'Site/SiteMailChimpList.php';

/**
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMailChimpSignupPage extends SiteMailingSignupPage
{
	// process phase
	// {{{ protected function save()

	protected function getList()
	{
		return new SiteMailChimpList($this->app);
	}

	// }}}
	// {{{ protected getSubscriberInfo();

	protected function getSubscriberInfo()
	{
		$info = array(
			'user_ip' => getenv("REMOTE_ADDR"),
		);

		return $info;
	}

	// }}}
}

?>
