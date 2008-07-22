<?php

require_once 'Site/pages/SiteArticlePage.php';

/**
 * Redirects to login page if not logged in
 *
 * @package   Site
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountPage extends SiteArticlePage
{
	// {{{ public function init()

	public function init()
	{
		parent::init();

		// redirect to login page if not logged in
		if (!$this->app->session->isLoggedIn() &&
			$this->source != 'account/login' &&
			$this->source != 'account/forgotpassword' &&
			$this->source != 'account/resetpassword' &&
			$this->source != 'account/edit')
				$this->app->relocate('account/login');
	}

	// }}}
}

?>
