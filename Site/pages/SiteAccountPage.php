<?php

require_once 'Site/pages/SiteArticlePage.php';

/**
 * @package   Site
 * @copyright 2006-2007 silverorange
 */
abstract class SiteAccountPage extends SiteArticlePage
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
