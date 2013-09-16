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
	// {{{ public function init()

	public function init()
	{
		if (!extension_loaded('redis')) {
			throw new SiteException('Redis module requires the redis '.
				'extension to be loaded.'
			);
		}

		$config = $this->app->config->redis;

		$this->redis = new Redis();
		$this->redis->connect($config->server);

		if ($config->prefix != '') {
			$prefix = $config->prefix;
			if (!preg_match('/:$/', $prefix)) {
				$prefix.= ':';
			}
			$this->redis->setOption(Redis::OPT_PREFIX, $prefix);
		}

		$this->redis->select($config->database);
	}

	// }}}
	// {{{ public function __call()

	public function __call($name, $arguments)
	{
		return call_user_func_array(
			array(
				$this->redis,
				$name
			),
			$arguments
		);
	}

	// }}}
}

?>
