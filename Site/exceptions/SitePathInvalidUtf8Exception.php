<?php

require_once 'Site/exceptions/SiteException.php';

/**
 * Thrown when the path we're looking up has invalid UFF-8 in it.
 *
 * @package   Site
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SitePathInvalidUtf8Exception extends SiteException
{
	// {{{ protected function getMessageAsHtml()

	/**
	 * Formats the exception's message as Html
	 *
	 * Subclassed to silence htmlspecialchars()'s warning, since we already know
	 * about the invalid UTF-8.
	 *
	 * @return string the cleaned exception message.
	 */
	protected function getMessageAsHtml()
	{
		$message = &htmlspecialchars($this->getMessage());
		return nl2br($message);
	}

	// }}}
}

?>
