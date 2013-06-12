<?php

require_once 'Site/exceptions/SiteException.php';

/**
 * Thrown when page is not authorized when http auth is used
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteNotAuthorizedException extends SiteException
{
	// {{{ public function __construct()

	/**
	 * Creates a new not authorized exception
	 *
	 * @param string $message the message of the exception.
	 * @param integer $code the code of the exception.
	 */
	public function __construct($message = null, $code = 0)
	{
		parent::__construct($message, $code);
		$this->title = Site::_('Not Authorized');
		$this->http_status_code = 401;
	}

	// }}}
}

?>
