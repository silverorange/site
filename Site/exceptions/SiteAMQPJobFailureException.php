<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Site/exceptions/SiteException.php';

/**
 * Exception thrown when a job fails
 *
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAMQPJobFailureException extends SiteException
{
	// {{{ protected properties

	/**
	 * The raw AMQP response body
	 *
	 * @var string
	 */
	protected $raw_body = '';

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new exception
	 *
	 * @param string  $message  the message of this exception.
	 * @param integer $code     optional. The error code of this exception.
	 * @param string  $raw_body optional. The raw AMQP response body.
	 */
	public function __construct($message, $code = 0, $raw_body = '')
	{
		parent::__construct($message, $code);
		$this->raw_body = (string)$raw_body;
	}

	// }}}
	// {{{ public function getRawBody()

	/**
	 * Gets the raw AMQP response body of this exception
	 *
	 * @return string the raw AMQP response body
	 */
	public function getRawBody()
	{
		return $this->raw_body;
	}

	// }}}
}

?>
