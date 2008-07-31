<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Site/exceptions/SiteException.php';

/**
 * Web application module for using memcache
 *
 * In general, the API mirrors the object-oriented API of the object-oriented
 * {@link http://ca.php.net/manual/en/ref.memcache.php memcache extension}.
 *
 * There are three levels of namespacing implemented to allow easier use of
 * memcached in Site applications. The first namespace level is the application
 * id. The second level is the application instance id. Both the first and
 * second level of namespace are set automatically and do not need to be
 * specified.
 *
 * The third namespacing level is optional and is used with the *Ns methods.
 * Optional namespacing allows flushing of the cache on a per-namespace level.
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

	/**
	 * @var array
	 */
	protected $ns_id_cache = array();

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

		$this->key_prefix = $this->app->id.'_';

		if ($this->app->hasModule('SiteMultipleInstanceModule')) {
			$instance = $this->app->getModule('SiteMultipleInstanceModule');
			$this->key_prefix.= $instance->getInstance()->shortname.'_';
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

	// storage and retrieval
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
	// {{{ public function delete()

	public function delete($key, $timeout = 0)
	{
		$key = $this->key_prefix.$key;
		return $this->memcache->delete($key, $timeout);
	}

	// }}}
	// {{{ public function increment()

	public function increment($key, $value = 1)
	{
		$key = $this->key_prefix.$key;
		return $this->memcache->increment($key, $value);
	}

	// }}}
	// {{{ public function decrement()

	public function decrement($key, $value = 1)
	{
		$key = $this->key_prefix.$key;
		return $this->memcache->decrement($key, $value);
	}

	// }}}

	// namespaces
	// {{{ public function addNs()

	public function addNs($ns, $key, $value, $flag, $expire = 86400)
	{
		$key = $this->getNsKey($ns, $key);
		return $this->add($key, $value, $flag, $expire);
	}

	// }}}
	// {{{ public function setNs()

	public function setNs($ns, $key, $value, $flag = 0, $expire = 86400)
	{
		$key = $this->getNsKey($ns, $key);
		return $this->set($key, $value, $flag, $expire);
	}

	// }}}
	// {{{ public function replaceNs()

	public function replaceNs($ns, $key, $value, $flag = 0, $expire = 86400)
	{
		$key = $this->getNsKey($ns, $key);
		return $this->replace($key, $value, $flag, $expire);
	}

	// }}}
	// {{{ public function getNs()

	public function getNs($ns, $key, &$flags = 0)
	{
		$key = $this->getNsKey($ns, $key);
		return $this->get($key, $flags);
	}

	// }}}
	// {{{ public function deleteNs()

	public function deleteNs($ns, $key, $timeout = 0)
	{
		$key = $this->getNsKey($ns, $key);
		return $this->delete($key, $timeout);
	}

	// }}}
	// {{{ public function flushNs()

	/**
	 * Flushes the cache for a single namespace
	 */
	public function flushNs($ns)
	{
		$key = $ns.'_key';

		$id = $this->increment($key);

		if ($id !== false) {
			$this->ns_id_cache[$ns] = $id;
		}
	}

	// }}}
	// {{{ protected function getNsKey()

	protected function getNsKey($ns, $key)
	{
		if (array_key_exists($ns, $this->ns_id_cache)) {
			$id = $this->ns_id_cache[$ns];
		} else {
			$id = $this->get($ns.'_key');
			if ($id === false) {
				$id = 0;
				$this->set($ns.'_key', $id);
			}
		}

		return $ns.'_'.$id.'_'.$key;
	}

	// }}}

	// general
	// {{{ public function flush()

	public function flush()
	{
		return $this->memcache->flush();
	}

	// }}}
	// {{{ public function getStats()

	public function getStats($type = '', $slab_id = 0, $limit = 100)
	{
		return $this->memcached->getStats();
	}

	// }}}
}

?>
