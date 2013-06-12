<?php

require_once 'Swat/exceptions/SwatException.php';

/**
 * Exception caused by the use of an invalid MAC on the shared secret page
 *
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteInvalidMacException extends SwatException
{
}

?>
