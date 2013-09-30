<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

/**
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAMQPJob
{
	protected $queue = null;
	protected $envelope = null;

	public function __construct(AMQPExchange $exchange, AMQPEnvelope $envelope,
		AMQPQueue $queue)
	{
		$this->exchange = $exchange;
		$this->envelope = $envelope;
		$this->queue = $queue;
	}

	// {{{ public function sendSuccess()

	/**
	 * @param string $response_message optional.
	 */
	public function sendSuccess($response_message = '')
	{
		$this->queue->ack($this->envelope->getDeliveryTag());

		if ($this->envelope->getReplyTo() != '') {
			$this->exchange->publish(
				json_encode(
					array(
						'status' => 'success',
						'body'   => $response_message,
					)
				),
				$this->envelope->getReplyTo()
			);
		}
	}

	// }}}
	// {{{ public function sendFail()

	/**
	 * @param string $response_message optional.
	 */
	public function sendFail($response_message = '')
	{
		$this->queue->ack($this->envelope->getDeliveryTag());

		if ($this->envelope->getReplyTo() != '') {
			$this->exchange->publish(
				json_encode(
					array(
						'status' => 'success',
						'body'   => $response_message,
					)
				),
				$this->envelope->getReplyTo()
			);
		}
	}

	// }}}
	// {{{ public function getBody()

	public function getBody()
	{
		return $this->envelope->getBody();
	}

	// }}}
}

?>
