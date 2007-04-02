<?php

require_once 'Site/exceptions/SiteException.php';

/**
 * Thrown when a possible session hijack is detected
 *
 * @package   Site
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSessionHijackException extends SiteException
{
	// {{{ public function __construct()

	/**
	 * Creates new session hijack exception
	 */
	public function __construct($user_agent)
	{
		parent::__construct(sprintf(
			'Possible session hijacking attempt thwarted. '.
			'Expected user agent: "%s"', $user_agent));
	}

	// }}}
}

?>
