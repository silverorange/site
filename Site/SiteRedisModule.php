<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Site/exceptions/SiteException.php';

/**
 * Web application module for using Redis
 *
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteRedisModule extends SiteApplicationModule
{
	// {{{ protected properties

	/**
	 * @var Redis
	 */
	protected $redis = null;

	/**
	 * @var boolean
	 */
	protected $connected = false;

	// }}}
	// {{{ public function init()

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

	protected function _connect()
	{
		if (!$this->connected) {
			$config = $this->app->config->redis;
			$this->redis->connect($config->server);
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
