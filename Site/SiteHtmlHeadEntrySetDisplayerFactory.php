<?php

require_once 'Concentrate/CacheArray.php';
require_once 'Concentrate/CacheMemcache.php';
require_once 'Concentrate/DataProvider.php';
require_once 'Concentrate/DataProvider/FileFinderDevelopment.php';
require_once 'Concentrate/DataProvider/FileFinderPear.php';
require_once 'Swat/SwatHtmlHeadEntrySet.php';
require_once 'Swat/SwatHtmlHeadEntrySetDisplayer.php';
require_once 'Site/SiteApplication.php';

/**
 * Builds an object to display HTML head entries for an application using
 * the Concentrate library
 *
 * @package   Site
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteHtmlHeadEntrySetDisplayerFactory
{
	// {{{ protected properties

	/**
	 * Indexed by application id
	 *
	 * @var array
	 */
	protected $displayers = array();

	// }}}
	// {{{ public function build()

	public function build(SiteApplication $app)
	{
		if (!isset($this->displayers[$app->id])) {
			$resources = $app->config->resources;
			$memcache  = $app->config->memcache;

			// build cache
			if ($memcache->cache_resources) {
				$memcached = new Memcached();
				$memcached->addServer($memcache->server, 11211);
				$cache = new Concentrate_CacheMemcache(
					$memcached, $memcache->app_ns);
			} else {
				$cache = new Concentrate_CacheArray();
			}

			// build data provider
			$data_provider = new Concentrate_DataProvider(array(
				'stat' => $resources->development,
			));

			// build concentrator
			$concentrator = new Concentrate_Concentrator(array(
				'cache'         => $cache,
				'data_provider' => $data_provider,
			));

			// load data files
			if ($resources->development) {
				$finder = new Concentrate_DataProvider_FileFinderDevelopment();
				$concentrator->loadDataFiles($finder->getDataFiles());
			} else {
				$finder = new Concentrate_DataProvider_FileFinderPear(
					$app->config->site->pearrc);

				$concentrator->loadDataFiles($finder->getDataFiles());
				$finder = new Concentrate_DataProvider_FileFinderDirectory(
					'../dependencies');

				$concentrator->loadDataFiles($finder->getDataFiles());
			}

			$this->displayers[$app->id] =
				new SwatHtmlHeadEntrySetDisplayer($concentrator);
		}

		return $this->displayers[$app->id];
	}

	// }}}
}

?>
