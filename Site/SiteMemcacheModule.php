<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Site/exceptions/SiteException.php';

/**
 * Web application module for using memcached
 *
 * In general, the API mirrors the object-oriented API of the object-oriented
 * {@link http://ca.php.net/manual/en/ref.memcache.php memcache extension}.
 *
 * There are three levels of namespacing implemented to allow easier use of
 * memcached in Site applications. The first namespace level is the application
 * as specified in {@link SiteMemcacheModule::$app_ns}. The second level is the
 * application instance id. The application namespace is usually set during the
 * configuration step of an application. The instance namespace is set
 * automatically.
 *
 * The third namespacing level is optional and is used with the *Ns methods.
 * Optional namespacing allows flushing of the cache on a per-namespace level.
 *
 * @package   Site
 * @copyright 2008-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMemcacheModule extends SiteApplicationModule
{
	// {{{ public properties

	/**
	 * @var string
	 */
	public $server = 'localhost';

	/**
	 * @var string
	 */
	public $app_ns = '';

	// }}}
	// {{{ protected properties

	/**
	 * @var Memcache
	 */
	protected $memcached;

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
		if (!extension_loaded('memcached')) {
			throw new SiteException('Memcache module requires the memcached '.
				'extension to be loaded.');
		}

		if ($this->app_ns == '') {
			throw new SiteException('Application namespace '.
				'(SiteMemcacheModule::$app_ns) must be set to initialize the '.
				'memcache module.');
		}

		$this->memcached = new Memcached();

		// add server to server pool
		$this->memcached->addServer($this->server, 11211);

		$this->key_prefix = $this->app_ns.'_';

		if ($this->app->hasModule('SiteMultipleInstanceModule')) {
			$instance = $this->app->getModule('SiteMultipleInstanceModule');

			if ($instance->getInstance() !== null)
				$this->setInstance($instance->getInstance());
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

	public function add($key, $value, $expiration = 0)
	{
		if (!$this->enabled())
			return false;

		$key = $this->key_prefix.$key;
		return $this->memcached->add($key, $value, $expiration);
	}

	// }}}
	// {{{ public function set()

	public function set($key, $value, $expiration = 0)
	{
		if (!$this->enabled())
			return false;

		$key = $this->key_prefix.$key;
		return $this->memcached->set($key, $value, $expiration);
	}

	// }}}
	// {{{ public function replace()

	public function replace($key, $value, $expiration = 0)
	{
		if (!$this->enabled())
			return false;

		$key = $this->key_prefix.$key;
		return $this->memcached->replace($key, $value, $expiration);
	}

	// }}}
	// {{{ public function get()

	public function get($key, $cache_cb = null, &$cas_token = null)
	{
		if (!$this->enabled())
			return false;

		if (is_array($key)) {
			foreach ($key as &$the_key) {
				$the_key = $this->key_prefix.$the_key;
			}
		} else {
			$key = $this->key_prefix.$key;
		}

		return $this->memcached->get($key, $cache_cb, $cas_token);
	}

	// }}}
	// {{{ public function delete()

	public function delete($key, $time = 0)
	{
		if (!$this->enabled())
			return false;

		$key = $this->key_prefix.$key;
		return $this->memcached->delete($key, $time);
	}

	// }}}
	// {{{ public function increment()

	public function increment($key, $offset = 1)
	{
		if (!$this->enabled())
			return false;

		$key = $this->key_prefix.$key;
		return $this->memcached->increment($key, $offset);
	}

	// }}}
	// {{{ public function decrement()

	public function decrement($key, $offset = 1)
	{
		if (!$this->enabled())
			return false;

		$key = $this->key_prefix.$key;
		return $this->memcached->decrement($key, $offset);
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

	public function getNs($ns, $key, $cache_cb = null, &$cas_token = null)
	{
		if (is_array($key)) {
			foreach ($key as &$the_key) {
				$the_key = $this->getNsKey($ns, $the_key);
			}
		} else {
			$key = $this->getNsKey($ns, $key);
		}

		return $this->get($key, $cache_cb, $cas_token);
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
	// {{{ public function setInstance()

	/**
	 * Manually specify the instance for the memcache module
	 *
 	 * If the application already specifies an instance, the SiteMemcacheModule
	 * will automatically use the specified instance for naming keys. In
	 * certain situations though, such as flushing the namespace for more than
	 * one instance in a system script, manually specifying the current instance
	 * can be useful.
	 *
	 * @param $instance SiteInstance|string The instance to use for memcache
	 *                                      keys, or optionally an instance
	 *                                      shortname.
	 */
	public function setInstance($instance)
	{
		$this->key_prefix = $this->app_ns.'_';

		if ($instance instanceof SiteInstance) {
			$shortname = $instance->shortname;
		} else {
			$shortname = $instance;
		}

		$this->key_prefix.= $shortname.'_';
	}

	// }}}
	// {{{ public function flush()

	public function flush($delay = 0)
	{
		return $this->memcached->flush($delay);
	}

	// }}}
	// {{{ public function getStats()

	public function getStats()
	{
		return $this->memcached->getStats();
	}

	// }}}
	// {{{ protected function enabled()

	/**
	 * Whether memcache is currently enabled on the site
	 *
	 * @return boolean
 	 */
	protected function enabled()
	{
		return $this->app->config->memcache->enabled;
	}

	// }}}
}

?>
