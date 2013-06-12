<?php

require_once 'Site/exceptions/SiteException.php';

/**
 * Thrown when something is not found
 *
 * @package   Site
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteNotFoundException extends SiteException
{
	// {{{ public function __construct()

	/**
	 * Creates a new not found exception
	 *
	 * @param string $message the message of the exception.
	 * @param integer $code the code of the exception.
	 */
	public function __construct($message = null, $code = 0)
	{
		parent::__construct($message, $code);
		$this->title = Site::_('Not Found');
		$this->http_status_code = 404;
	}

	// }}}
}

?>
