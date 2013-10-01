<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

/**
 * A single job received by a work queue job processor
 *
 * SiteAMQPJob is received by the {@link SiteAMQPApplication::doWork()} method.
 *
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAMQPJob
{
	// {{{ protected properties

	/**
	 * The exchange where responses to this job can be published
	 *
	 * @var AMQPExchange
	 */
	protected $exchange = null;

	/**
	 * The queue where this job originated
	 *
	 * @var AMQPQueue
	 */
	protected $queue = null;

	/**
	 * The envelope containing this job's message data
	 *
	 * @var AMQPEnvelope
	 */
	protected $envelope = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new job
	 *
	 * @param AMQPExchange $exchange the exchange where responses can be
	 *                               published.
	 * @param AMQPEnvelope $envelope the envelope containg this job's message
	 *                               data.
	 * @param AMQPQueue    $queue    the queue where this job originated.
	 */
	public function __construct(AMQPExchange $exchange, AMQPEnvelope $envelope,
		AMQPQueue $queue)
	{
		$this->exchange = $exchange;
		$this->envelope = $envelope;
		$this->queue = $queue;
	}

	// }}}
	// {{{ public function sendSuccess()

	/**
	 * Sends result data and successful status back to the AMQP exchange
	 * for this job
	 *
	 * This job is removed from the work queue.
	 *
	 * @param string $response_message optional. The result data. This is
	 *                                 only useful if performing synchronous
	 *                                 methods.
	 *
	 * @return void
	 */
	public function sendSuccess($response_message = '')
	{
		$this->queue->ack($this->envelope->getDeliveryTag());

		if ($this->envelope->getReplyTo() != '' &&
			$this->envelope->getCorrelationId() != '') {
			$this->exchange->publish(
				json_encode(
					array(
						'status' => 'success',
						'body'   => $response_message,
					)
				),
				$this->envelope->getReplyTo(),
				AMQP_NOPARAM,
				array(
					'correlation_id' => $this->envelope->getCorrelationId(),
				)
			);
		}
	}

	// }}}
	// {{{ public function sendFail()

	/**
	 * Sends result data and known failure status back to the AMQP exchange
	 * for this job
	 *
	 * This job is removed from the work queue.
	 *
	 * @param string $response_message optional. If performing synchronous
	 *                                 operations, this will be the message
	 *                                 content of a thrown exception.
	 *
	 * @return void
	 */
	public function sendFail($response_message = '')
	{
		$this->queue->ack($this->envelope->getDeliveryTag());

		if ($this->envelope->getReplyTo() != '') {
			$this->exchange->publish(
				json_encode(
					array(
						'status' => 'fail',
						'body'   => $response_message,
					)
				),
				$this->envelope->getReplyTo(),
				AMQP_NOPARAM,
				array(
					'correlation_id' => $this->envelope->getCorrelationId(),
				)
			);
		}
	}

	// }}}
	// {{{ public function getBody()

	/**
	 * Gets the message body of this job
	 *
	 * @return string the message body of this job.
	 */
	public function getBody()
	{
		return $this->envelope->getBody();
	}

	// }}}
}

?>
