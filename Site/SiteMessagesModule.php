<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Swat/SwatMessage.php';

/**
 * Web application module for admin messages
 *
 * @package Admin
 * @copyright silverorange 2004
 */
class AdminMessagesModule extends SiteApplicationModule
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
