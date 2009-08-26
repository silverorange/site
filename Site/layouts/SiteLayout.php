<?php

require_once 'Site/SiteObject.php';
require_once 'Site/SiteApplication.php';
require_once 'Site/SiteLayoutData.php';
require_once 'Site/exceptions/SiteInvalidPropertyException.php';

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
		$this->startCapture('html_head_entries');
		$this->html_head_entries->display($this->app->getBaseHref());
		$this->endCapture();
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
}

?>
