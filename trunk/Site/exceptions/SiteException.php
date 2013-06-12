<?php

require_once 'Swat/exceptions/SwatException.php';
require_once 'PEAR.php';

/**
 * An exception in Site package
 *
 * @package   Site
 * @copyright 2004-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteException extends SwatException
{
	// {{{ public properties

	public $title = null;
	public $http_status_code = 500;

	// }}}
	// {{{ public function __construct()

	public function __construct($message = null, $code = 0)
	{
		if (is_object($message) && ($message instanceof PEAR_Error)) {
			$error = $message;
			$message = $error->getMessage();
			$message .= "\n".$error->getUserInfo();
			$code = $error->getCode();
		}

		parent::__construct($message, $code);
	}

	// }}}
}

?>
