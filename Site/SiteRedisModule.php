<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Site/SiteApplicationModule.php';
require_once 'Site/exceptions/SiteException.php';

/**
 * Application module for using Redis
 *
 * @package   Site
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteRedisModule extends SiteApplicationModule
{
	// {{{ protected properties

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

	// }}}
	// {{{ public function init()

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

	// }}}
	// {{{ public function __call()

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
				array(
					$this->redis,
					$name
				),
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

	// }}}
	// {{{ protected function _connect()

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

				if ($config->prefix != '') {
					$prefix = $config->prefix;
					if (!preg_match('/:$/', $prefix)) {
						$prefix.= ':';
					}
					$this->redis->setOption(Redis::OPT_PREFIX, $prefix);
				}

				$this->connected = true;
			} catch (RedisException $e) {
			}
		}
	}

	// }}}
}

?>
