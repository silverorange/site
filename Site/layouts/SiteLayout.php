<?php

require_once 'Swat/SwatHtmlHeadEntrySet.php';
require_once 'Swat/SwatHtmlHeadEntrySetDisplayer.php';
require_once 'Site/SiteObject.php';
require_once 'Site/SiteApplication.php';
require_once 'Site/SiteLayoutData.php';
require_once 'Site/SiteHtmlHeadEntrySetDisplayerFactory.php';
require_once 'Site/exceptions/SiteInvalidPropertyException.php';
require_once 'Concentrate/CLI.php';

/**
 * Base class for a layout
 *
 * @package   Site
 * @copyright 2005-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteLayout extends SiteObject
{
	// {{{ public properties

	/**
	 * @var SiteWebApplication
	 */
	public $app = null;

	/**
	 * @var SiteLayoutData
	 */
	public $data = null;

	// }}}
	// {{{ protected properties

	/**
	 * @var SwatHtmlHeadEntrySet
	 */
	protected $html_head_entries;

	/**
	 * @var array
	 *
	 * @see SiteLayout::addBodyClass()
	 * @see SiteLayout::removeBodyClass()
	 */
	protected $body_classes = array();

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

		if (isset($this->data->$name)) {
			if ($this->capture_prepend) {
				$this->data->$name = ob_get_clean().$this->data->$name;
			} else {
				$this->data->$name.= ob_get_clean();
			}
		} else {
			$this->data->$name = ob_get_clean();
		}

		$this->current_capture = null;
	}

	// }}}
	// {{{ public function clear()

	public function clear($name)
	{
		if (!isset($this->data->$name)) {
			throw new SiteException("Layout data property '{$name}' does not ".
				'exist and cannot be cleared.');
		}

		$this->data->$name = '';
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		$this->data->basehref = $this->app->getBaseHref();
		$this->data->title = '';
		$this->data->html_title = '';

		if (isset($this->app->config->site->meta_description)) {
			$this->data->meta_description =
				SwatString::minimizeEntities(
					$this->app->config->site->meta_description);
		} else {
			$this->data->meta_description = '';
		}

		$this->data->analytics        = '';
		$this->data->meta_keywords    = '';
		$this->data->extra_headers    = '';
		$this->data->extra_footers    = '';
		$this->data->mobile_meta_tags = '';

		if (isset($this->app->mobile) && $this->app->mobile->isMobileUrl()) {
			$this->addBodyClass('mobile');

			ob_start();
			$this->app->mobile->displayMobileMetaTags();
			$this->data->mobile_meta_tags = ob_get_clean();
		}
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
	}

	// }}}
	// {{{ public function addHtmlHeadEntry()

	/**
	 * @param SwatHtmlHeadEntry|string $entry
	 */
	public function addHtmlHeadEntry($entry)
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
	// {{{ public function addBodyClass()

	/**
	 * Adds a body class to this layout
	 *
	 * @param string|array $class either a string or an array containing the
	 *                             class names to add. If the class names
	 *                             already exist in this layout, they are
	 *                             ignored.
	 *
	 * @return void
	 */
	public function addBodyClass($class)
	{
		if (!is_array($class)) {
			$class = array($class);
		}

		$this->body_classes = array_unique(
			array_merge($this->body_classes, $class));
	}

	// }}}
	// {{{ public function removeBodyClass()

	/**
	 * Removes a body class from this layout
	 *
	 * @param string|array $class either a string or an array containing the
	 *                             class names to remove. If the class names
	 *                             do not exist in this layout, they are
	 *                             ignored.
	 *
	 * @return void
	 */
	public function removeBodyClass($class)
	{
		if (!is_array($class)) {
			$class = array($class);
		}

		$this->body_classes = array_diff($this->body_classes, $class);
	}

	// }}}

	// complete phase
	// {{{ public function complete()

	public function complete()
	{
		$this->completeHtmlHeadEntries();
		$this->completeBodyClasses();
	}

	// }}}
	// {{{ protected function completeBodyClasses()

	protected function completeBodyClasses()
	{
		// don't overwrite custom use of body_class data field
		if (!isset($this->data->body_classes)) {
			$this->data->body_classes = '';
			if (count($this->body_classes) > 0) {
				$this->data->body_classes = sprintf(' class="%s"',
					SwatString::minimizeEntities(
						implode(' ', $this->body_classes)));
			}
		}
	}

	// }}}
	// {{{ protected function completeHtmlHeadEntries()

	protected function completeHtmlHeadEntries()
	{
		$resources = $this->app->config->resources;
		$factory   = new SiteHtmlHeadEntrySetDisplayerFactory();
		$displayer = $factory->build($this->app);

		// get resource tag
		$tag = $this->getTagByFlagFile();
		if ($tag === null) {
			if ($this->app->config->resources->tag === null) {
				// support deprecated site.resource_tag config option
				$tag = $this->app->config->site->resource_tag;
			} else {
				$tag = $resources->tag;
			}
		}

		// get combine option
		$combine = ($resources->combine &&
			$this->getCombineEnabledByFlagFile());

		// get minify option
		$minify = ($resources->minify &&
			$this->getMinifyEnabledByFlagFile());

		$this->startCapture('html_head_entries');

		$displayer->display(
			$this->html_head_entries,
			$this->app->getBaseHref(),
			$tag,
			$combine,
			$minify);

		$this->endCapture();
	}

	// }}}
	// {{{ protected function getCombineEnabledByFlagFile()

	/**
	 * Gets whether or not the flag file generated during the concentrate build
	 * exists
	 *
	 * @return boolean true if the file exists, false if it does not.
	 */
	protected function getCombineEnabledByFlagFile()
	{
		$www_root = dirname($_SERVER['SCRIPT_FILENAME']);
		$filename = $www_root.DIRECTORY_SEPARATOR.
			Concentrate_CLI::FILENAME_FLAG_COMBINED;

		return file_exists($filename);
	}

	// }}}
	// {{{ protected function getMinifyEnabledByFlagFile()

	/**
	 * Gets whether or not the flag file generated during the concentrate build
	 * exists
	 *
	 * @return boolean true if the file exists, false if it does not.
	 */
	protected function getMinifyEnabledByFlagFile()
	{
		$www_root = dirname($_SERVER['SCRIPT_FILENAME']);
		$filename = $www_root.DIRECTORY_SEPARATOR.
			Concentrate_CLI::FILENAME_FLAG_MINIFIED;

		return file_exists($filename);
	}

	// }}}
	// {{{ protected function getTagByFlagFile()

	/**
	 * Gets the resource tag from a flag file that can be generated during
	 * a site build process
	 *
	 * If the flag file is present, the tag value in the file overrides the
	 * value in the site's configuration.
	 *
	 * @return string the resource tag or null if the flag file is not present.
	 */
	protected function getTagByFlagFile()
	{
		$tag = null;

		$www_root = dirname($_SERVER['SCRIPT_FILENAME']);
		$filename = $www_root.DIRECTORY_SEPARATOR.'.resource-tag';

		if (file_exists($filename) && is_readable($filename)) {
			$tag = trim(file_get_contents($filename));
		}

		return $tag;
	}

	// }}}
}

?>
