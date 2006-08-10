<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Swat/SwatMessage.php';

/**
 * Web application module for site messages
 *
 * This module works by adding {@link SwatMessage} objects to the session. As
 * such, it depends on the {@link SiteSessionModule}.
 *
 * @package   Site
 * @copyright 2004-2006
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMessagesModule extends SiteApplicationModule
{
    // {{{ public function init()

	public function init()
	{
		if (!isset($_SESSION['messages']) || !is_array($_SESSION['messages']))
			$_SESSION['messages'] = array();
	}

    // }}}
    // {{{ public function add()

	public function add(SwatMessage $message)
	{
		$_SESSION['messages'][] = $message;
	}

    // }}}
    // {{{ public function getAll()

	public function getAll()
	{
		$ret = $_SESSION['messages'];
		$_SESSION['messages'] = array();
		return $ret;
	}

    // }}}
}

?>
