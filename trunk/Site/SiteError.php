<?php

require_once 'Swat/SwatError.php';

/**
 * An error in Site
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteError extends SwatError
{
	// {{{ public static function setupHandler()

	/**
	 * Set the PHP error handler to use SiteError
	 */
	public static function setupHandler()
	{
		/*
		 * All run-time errors except for forwards compatibility
		 * (E_STRICT) are handled by default.
		 */
		set_error_handler(array('SwatError', 'handle'),
			E_USER_ERROR | E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE);
	}

	// }}}
}

?>
