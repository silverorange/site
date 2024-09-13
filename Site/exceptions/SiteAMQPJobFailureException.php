<?php

/**
 * Exception thrown when a job fails
 *
 * @package   Site
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAMQPJobFailureException extends SiteAMQPJobException
{


	/**
	 * The raw AMQP response body
	 *
	 * @var string
	 */
	protected $raw_body = '';




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




	/**
	 * Gets the raw AMQP response body of this exception
	 *
	 * @return string the raw AMQP response body
	 */
	public function getRawBody()
	{
		return $this->raw_body;
	}


}

?>
