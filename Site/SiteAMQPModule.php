<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for sending messages to an AMQP broker
 *
 * This uses pecl-amqp and works with AMQP 0-9-1, which is supported by
 * RabbitMQ.
 *
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAMQPModule extends SiteApplicationModule
{
	// {{{ protected properties

	/**
	 * @var array
	 */
	protected $exchanges = array();

	/**
	 * @var AMQPConnection
	 */
	protected $connection = null;

	/**
	 * @var AMQPChannel
	 */
	protected $channel = null;

	// }}}
	// {{{ public function init()

	public function init()
	{
		$config = $this->app->getModule('SiteConfigModule');
		$this->default_namespace = $config->amqp->default_namespace;
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The AMQP module depends on the SiteConfigModule feature.
	 *
	 * @return array an array of {@link SiteApplicationModuleDependency}
	 *                        objects defining the features this module
	 *                        depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();
		$depends[] = new SiteApplicationModuleDependency('SiteConfigModule');
		return $depends;
	}

	// }}}
	// {{{ public function doAsyncNs()

	public function doAsyncNs($namespace, $exchange, $message,
		array $attributes = array())
	{
		$this->connect();
		$this->getExchange($namespace, $exchange)->publish(
			(string)$message,
			$attributes
		);
	}

	// }}}
	// {{{ public function doAsync()

	public function doAsync($exchange, $message, array $attributes = array())
	{
		$this->doAsyncNs(
			$this->default_namespace,
			$exchange,
			$message,
			$attributes
		);
	}

	// }}}
	// {{{ public function doSyncNs()

	public function doSyncNs($namespace, $exchange, $message,
		array $attributes = array())
	{
		$this->connect();

		$correlation_id = uniqid(true);

		$reply_queue = new AMQPQueue($this->channel);
		$reply_queue->setFlags(AMQP_EXCLUSIVE);
		$reply_queue->declare();

		$attributes = array_merge(
			$attributes,
			array(
				'correlation_id' => $correlation_id,
				'reply_to'       => $reply_queue->getName()
			)
		);

		$this->getExchange($namespace, $exchange)->publish(
			(string)$message,
			$attributes,
			AMQP_MANDATORY | AMQP_IMMEDIATE
		);

		$response = null;

		$callback = function(AMQPEnvelope $envelope, AMQPQueue $queue)
			use $response
		{
			if ($envelope->getCorrelationId() === $correlation_id) {
				$response = $envelope->getBody();
				if (json_decode($response, true) === null ||
					!isset($response['status'])) {
					throw new Exception();
				}
				if ($response['status'] === 'fail') {
					throw new Exception($response['body']);
				}
				$response = $response['body'];
			}
		};

		while ($response === null) {
			$reply_queue->consume($callback);
		}

		return $response;
	}

	// }}}
	// {{{ public function doSync()

	public function doSync($exchange, $message, array $attributes = array())
	{
		return $this->doSyncNs(
			$this->default_namespace,
			$exchange,
			$message,
			$attributes
		);
	}

	// }}}
	// {{{ protected function connect()

	/**
	 * Lazily creates an AMQP connection
	 */
	protected function connect()
	{
		if ($this->connection === null && class_exists('AMQPConnection')) {
			$config = $this->app->getModule('SiteConfigModule');
			$server_parts = explode(':', trim($config->amqp->server), 2);
			$host = $server_parts[0];
			$port = (count($server_parts) === 2) ? $server_parts[1] : 5672;

			$this->connection = new AMQPConnection();
			$this->connection->setHost($host);
			$this->connection->setPort($port);
			$this->connection->connect();

			$this->channel = new AMQPChannel($this->connection);
		}
	}

	// }}}
	// {{{ protected function getExchange()

	protected function getExchange($namespace, $name)
	{
		$key = $namespace.'.'.$name;
		if (!isset($this->exchanges[$key])) {
			$exchange = new AMQPExchange($this->channel);
			$exchange->setName($key);
			$exchange->setType(AMQP_EX_TYPE_DIRECT);
			$exchange->setFlags(AMQP_DURABLE);
			$exchange->declare();

			$queue = new AMQPQueue($this->channel);
			$queue->setName($key);
			$queue->setFlags(AMQP_DURABLE);
			$queue->declare();
			$queue->bind($key);

			$this->exchanges[$key] = $exchange;
		}

		return $this->exchanges[$key];
	}

	// }}}
}

?>
