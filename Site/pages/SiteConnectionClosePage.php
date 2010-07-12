<?php

require_once 'Site/pages/SitePage.php';

/**
 * Connection: Close page
 *
 * This page is a work-around for a Safari-OS X bug:
 * {@link http://lists.apple.com/archives/macnetworkprog/2006/Dec/msg00021.html}.
 * See also {@link https://bugs.webkit.org/show_bug.cgi?id=5760}.
 *
 * @package   Site
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteConnectionClosePage extends SitePage
{
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout($this->app,
			dirname(__FILE__).'/../layouts/xhtml/blank.php');
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		header('Connection: close');
	}

	// }}}
}

?>
