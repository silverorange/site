<?php

/**
 * Application module for using Redis
 *
 * @package   Site
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteRedisModule extends SiteApplicationModule
{


	/**
	 * The proxied Redis object of this module
	 *
	 * @var Redis
	 */
	protected $redis = null;

	/**
	 * Whether or not this module is connected to Redis
	 *
	 * @var boolean
	 */
	protected $connected = false;




	/**
	 * Initializes this module
	 *
	 * Ensures the phpredis extension exists.
	 *
	 * @throws SiteException if the phpredis module is not available.
	 */
	public function init()
	{
		if (!extension_loaded('redis')) {
			throw new SiteException(
				'Redis module requires the redis extension to be loaded.'
			);
		}

		$this->redis = new Redis();
	}




	/**
	 * Passes proxied calls to the redis object
	 *
	 * For full API, see {@link https://github.com/nicolasff/phpredis}.
	 *
	 * @param string $name      the proxied method name.
	 * @param array  $arguments the proxied method arguments.
	 *
	 * @return mixed the proxied result value.
	 */
	public function __call($name, $arguments)
	{
		try {
			$this->_connect();

			return call_user_func_array(
				[$this->redis, $name],
				$arguments
			);

		} catch (RedisException $e) {
			if ($e->getMessage() === 'Connection lost' ||
				$e->getMessage() === 'Redis server went away' ||
				$e->getMessage() === 'read error on connection') {
				$this->connected = false;
			}
			throw $e;
		}
	}




	/**
	 * Connects to the Redis server if not already connected
	 *
	 * This also takes care of setting the application database and prefix
	 * as specified in the application configuration.
	 *
	 * This method's name starts with an underscore so as not to override
	 * the proxied connect() method.
	 *
	 * @return void
	 */
	protected function _connect()
	{
		if (!$this->connected) {
			$config = $this->app->getModule('SiteConfigModule')->redis;

			$parts = explode(':', trim($config->server), 2);
			$address = $parts[0];
			$port = (count($parts) === 2) ? $parts[1] : 6379;

			$this->redis->connect($address, $port);

			try {
				$this->redis->select($config->database);

				// Do a ping with PHP notices suppressed. This checks for the
				// protected mode connection error since Redis 3.2.0. Errors
				// are suppressed because the phpredis extension raises a
				// notice on failure.
				$reporting = error_reporting(0);
				$this->redis->ping();
				error_reporting($reporting);

				if ($config->prefix != '') {
					$prefix = $config->prefix;
					if (!preg_match('/:$/', $prefix)) {
						$prefix.= ':';
					}
					$this->redis->setOption(Redis::OPT_PREFIX, $prefix);
				}

				$this->connected = true;
			} catch (RedisException $e) {
				// Handle Redis 3.2.0 protected mode connection error. See
				// https://github.com/phpredis/phpredis/issues/831
				$protected_mode_error =
					'protocol error, got \'n\' as reply type byte';

				if ($e->getMessage() === $protected_mode_error) {
					throw new RedisException(
						'Protected mode prevented connection to Redis. '.
						'Check your Redis configuration and try again.'
					);
				}
			}
		}
	}


}

?>
