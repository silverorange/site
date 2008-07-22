<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Site/exceptions/SiteException.php';

/**
 * Web application module for using memcache
 *
 * General API mirrors the object-oriented API of the object-oriented
 * {@link http://ca.php.net/manual/en/ref.memcache.php memcache extension}.
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMemcacheModule extends SiteApplicationModule
{
	// {{{ public properties

	/**
	 * @var string
	 */
	public $server = 'localhost';

	// }}}
	// {{{ protected properties

	/**
	 * @var Memcache
	 */
	protected $memcache;

	/**
	 * @var string
	 */
	protected $key_prefix = '';

	// }}}
	// {{{ public function init()

	public function init()
	{
		if (!extension_loaded('memcache')) {
			throw new SiteException('Memcache module requires the memcache '.
				'extension to be loaded.');
		}

		$this->memcache = new Memcache();
		$this->memcache->pconnect($this->server);

		if ($this->app->hasModule('SiteMultipleInstanceModule')) {
			$instance = $this->app->getModule('SiteMultipleInstanceModule');
			$this->key_prefix = $instance->getInstance()->shortname.'_';
		}
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The site memcached module optionally depends on the
	 * SiteMultipleInstanceModule feature.
	 *
	 * @return array an array of {@link SiteModuleDependency} objects defining
	 *                        the features this module depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();

		$depends[] = new SiteApplicationModuleDependency(
			'SiteMultipleInstanceModule', false);

		return $depends;
	}

	// }}}
	// {{{ public function add()

	public function add($key, $value, $flag, $expire)
	{
		$key = $this->key_prefix.$key;
		return $this->memcache->add($key, $value, $flag, $expire);
	}

	// }}}
	// {{{ public function set()

	public function set($key, $value, $flag = 0, $expire = 0)
	{
		$key = $this->key_prefix.$key;
		return $this->memcache->set($key, $value, $flag, $expire);
	}

	// }}}
	// {{{ public function replace()

	public function replace($key, $value, $flag = 0, $expire = 0)
	{
		$key = $this->key_prefix.$key;
		return $this->memcache->replace($key, $value, $flag, $expire);
	}

	// }}}
	// {{{ public function get()

	public function get($key, &$flags = 0)
	{
		$key = $this->key_prefix.$key;
		return $this->memcache->get($key, $flags);
	}

	// }}}
	// {{{ public function flush()

	public function flush()
	{
		return $this->memcache->flush();
	}

	// }}}
	// {{{ public function delete()

	public function delete($key, $timeout = 0)
	{
		$key = $this->key_prefix.$key;
		return $this->memcache->delete($key, $timeout);
	}

	// }}}
	// {{{ getStats()

	public function getStats($type = '', $slab_id = 0, $limit = 100)
	{
		return $this->memcached->getStats();
	}

	// }}}
}

?>
