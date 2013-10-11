<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Site/exceptions/SiteAMQPJobException.php';

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

	/**
	 * Whether or not a response was sent for this job
	 *
	 * @var boolean
	 */
	protected $response_sent = false;

	/**
	 * Whether or not this job was requeued
	 *
	 * @var boolean
	 */
	protected $requeued = false;

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
	 *
	 * @throws SiteAMQPJobException if the job has already sent a success or
	 *         fail response, or if this job has been requeued.
	 */
	public function sendSuccess($response_message = '')
	{
		$this->sendResponse($response_message, 'success');
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
	 *
	 * @throws SiteAMQPJobException if the job has already sent a success or
	 *         fail response, or if this job has been requeued.
	 */
	public function sendFail($response_message = '')
	{
		$this->sendResponse($response_message, 'fail');
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
	// {{{ public function requeue()

	/**
	 * Requeues this job
	 *
	 * If this job was already requeued, this method does nothing.
	 *
	 * @return void
	 *
	 * @throws SiteAMQPJobException if the job has already sent a success or
	 *         fail response.
	 */
	public function requeue()
	{
		if ($this->response_sent) {
			throw new SiteAMQPJobException(
				'Can not requeue message because response was already sent.'
			);
		}

		if (!$this->requeued) {
			$this->queue->nack($this->envelope->getDeliveryTag(), AMQP_REQUEUE);
			$this->requeued = true;
		}
	}

	// }}}
	// {{{ protected function sendResponse()

	/**
	 * Sends result data and status back to the AMQP exchange for this job
	 *
	 * This job is removed from the work queue.
	 *
	 * @param string $message if performing synchronous operations, this value
	 *                        will be returned to the caller.
	 * @param string $status  if performing synchronous operations, this
	 *                        status will be returned to the caller.
	 *
	 * @return void
	 *
	 * @throws SiteAMQPJobException if the job has already sent a success or
	 *         fail response, or if this job has been requeued.
	 */
	protected function sendResponse($message, $status)
	{
		if ($this->requeued) {
			throw new SiteAMQPJobException(
				'Can not send response because job was already requeued.'
			);
		}

		if ($this->response_sent) {
			throw new SiteAMQPJobException(
				'Can not send response because response was already sent.'
			);
		}

		$this->queue->ack($this->envelope->getDeliveryTag());

		if ($this->envelope->getReplyTo() != '' &&
			$this->envelope->getCorrelationId() != '') {
			$this->exchange->publish(
				json_encode(
					array(
						'status' => $status,
						'body'   => $message,
					)
				),
				$this->envelope->getReplyTo(),
				AMQP_NOPARAM,
				array(
					'correlation_id' => $this->envelope->getCorrelationId(),
				)
			);
		}

		$this->response_sent = true;
	}

	// }}}
}

?>
