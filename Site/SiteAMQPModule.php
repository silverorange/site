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
		// no initialization required
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
	// {{{ public function doForeground()

	public function doForeground($function, $job)
	{
	}

	// }}}
	// {{{ public function doBackgroundLow()

	public function doBackgroundLow($function, $job)
	{
		$this->connect();
		$this->getExchange($function)->publish((string)$job);
	}

	// }}}
	// {{{ public function doBackgroundHigh()

	public function doBackgroundHigh($function, $job)
	{
		$this->doBackgroundLow($function, $job);
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

	protected function getExchange($name)
	{
		if (!isset($this->exchanges[$name])) {
			$config = $this->app->getModule('SiteConfigModule');
			$prefix = $config->amqp->prefix;

			$exchange = new AMQPExchange($this->channel);
			$exchange->setName($prefix.'.'.$name);
			$exchange->setType(AMQP_EX_TYPE_DIRECT);
			$exchange->setFlags(AMQP_DURABLE);
			$exchange->declare();

			$queue = new AMQPQueue($this->channel);
			$queue->setName($prefix.'.'.$name);
			$queue->setFlags(AMQP_DURABLE);
			$queue->declare();
			$queue->bind($prefix.'.'.$name);

			$this->exchanges[$name] = $exchange;
		}

		return $this->exchanges[$name];
	}

	// }}}
}

?>
