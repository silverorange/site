<?php

require_once 'Concentrate/CacheArray.php';
require_once 'Concentrate/CacheMemcache.php';
require_once 'Concentrate/DataProvider.php';
//:require_once 'Concentrate/DataProviderMemcache.php';
require_once 'Concentrate/DataProvider/FileFinderDevelopment.php';
require_once 'Concentrate/DataProvider/FileFinderPear.php';
require_once 'Site/SiteObject.php';
require_once 'Site/SiteApplication.php';
require_once 'Site/SiteLayoutData.php';
require_once 'Site/exceptions/SiteInvalidPropertyException.php';
require_once 'Swat/SwatHtmlHeadEntrySet.php';
require_once 'Swat/SwatHtmlHeadEntrySetDisplayer.php';

/**
 * Base class for a layout
 *
 * @package   Site
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteLayout extends SiteObject
{
	// {{{ public properties

	public $app = null;
	public $data = null;

	// }}}
	// {{{ protected properties

	protected $html_head_entries;

	// }}}
	// {{{ private properties

	private $filename = null;
	private $current_capture = null;
	private $capture_prepend = false;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, $filename = null)
	{
		$this->app = $app;
		$this->html_head_entries = new SwatHtmlHeadEntrySet();

		if ($filename === null)
			$filename = 'Site/layouts/xhtml/default.php';

		$this->filename = $filename;
		$this->data = new SiteLayoutData();
	}

	// }}}
	// {{{ public function setFilename()

	public function setFilename($filename)
	{
		$this->filename = $filename;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		$this->data->display($this->filename);
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		$this->data->basehref = $this->app->getBaseHref();
		$this->data->title = '';
		$this->data->html_title = '';

		if (isset($this->app->config->site->meta_description))
			$this->data->meta_description =
				$this->app->config->site->meta_description;
		else
			$this->data->meta_description = '';

		$this->data->meta_keywords = '';
		$this->data->extra_headers = '';
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
	}

	// }}}
	// {{{ public function build()

	public function build()
	{
	}

	// }}}
	// {{{ public function finalize()

	public function finalize()
	{
	}

	// }}}
	// {{{ public function complete()

	public function complete()
	{
		$this->completeHtmlHeadEntries();
	}

	// }}}
	// {{{ public function startCapture()

	public function startCapture($name, $prepend = false)
	{
		if ($this->current_capture !== null)
			throw new SiteException('Capture already in progress.');

		$this->current_capture = $name;
		$this->capture_prepend = $prepend;
		ob_start();
	}

	// }}}
	// {{{ public function endCapture()

	public function endCapture()
	{
		if ($this->current_capture === null)
			throw new SiteException('No capture was started.');

		$name = $this->current_capture;

		if ($this->data->exists($name))
			if ($this->capture_prepend)
				$this->data->$name = ob_get_clean().$this->data->$name;
			else
				$this->data->$name.= ob_get_clean();
		else
			$this->data->$name = ob_get_clean();

		$this->current_capture = null;
	}

	// }}}
	// {{{ public function clear()

	public function clear($name)
	{
		if (!$this->data->exists($name))
			throw new SiteException("Layout data property '{$name}' does not ".
				'exist and cannot be cleared.');

		$this->data->$name = '';
	}

	// }}}
	// {{{ public function addHtmlHeadEntry()

	public function addHtmlHeadEntry(SwatHtmlHeadEntry $entry)
	{
		$this->html_head_entries->addEntry($entry);
	}

	// }}}
	// {{{ public function addHtmlHeadEntrySet()

	public function addHtmlHeadEntrySet(SwatHtmlHeadEntrySet $set)
	{
		$this->html_head_entries->addEntrySet($set);
	}

	// }}}
	// {{{ protected function completeHtmlHeadEntries()

	protected function completeHtmlHeadEntries()
	{
		$resources = $this->app->config->resources;
		$memcache  = $this->app->config->memcache;

		$time = microtime();

		// build data-file finder
		if ($resources->development) {
			$finder = new Concentrate_DataProvider_FileFinderDevelopment();
		} else {
			$finder = new Concentrate_DataProvider_FileFinderPear(
				$this->app->config->site->pearrc);
		}

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
		$concentrator->loadDataFiles($finder->getDataFiles());

		// get resource tag
		if ($this->app->config->resources->tag === null) {
			// support deprecated site.resource_tag config option
			$tag = $this->app->config->site->resource_tag;
		} else {
			$tag = $resources->tag;
		}

		// display head entries
		$this->startCapture('html_head_entries');

		$displayer = new SwatHtmlHeadEntrySetDisplayer($concentrator);
		$displayer->display($this->html_head_entries,
			$this->app->getBaseHref(), $tag,
			$resources->combine,
			$resources->minify);

		 // TODO: remove debug
		 echo "\t<!-- ", (microtime() - $time) * 1000 , " ms to sort and display head entries -->\n";

		$this->endCapture();
	}

	// }}}
}

?>
