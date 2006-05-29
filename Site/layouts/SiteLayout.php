<?php

require_once 'Site/SiteObject.php';
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
	// {{{ private properties

	private $filename = null;
	private $current_capture = null;
	private $capture_prepend = false;
	private $html_head_entries = array();

	// }}}
	// {{{ public function __construct()

	public function __construct($app, $filename = null)
	{
		$this->app = $app;

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
		$this->data->meta_description = '';
		$this->data->meta_keywords = '';
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
		$this->startCapture('html_head_entries');
		$this->displayHtmlHeadEntries();
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

		if (isset($this->data->$name))
			if ($this->capture_prepend)
				$this->data->$name = ob_get_clean().$this->data->$name;
			else
				$this->data->$name.= ob_get_clean();
		else
			$this->data->$name = ob_get_clean();

		$this->current_capture = null;
	}

	// }}}
	// {{{ public function getHtmlHeadEntries()

	public function getHtmlHeadEntries()
	{
		return $this->html_head_entries;
	}

	// }}}
	// {{{ public function addHtmlHeadEntry()

	public function addHtmlHeadEntry(SwatHtmlHeadEntry $entry)
	{
		$this->html_head_entries =
			array_merge($this->html_head_entries,
				array($entry->getUri() => $entry));
	}

	// }}}
	// {{{ public function addHtmlHeadEntries()

	public function addHtmlHeadEntries($entries)
	{
		if (is_array($entries))
			$this->html_head_entries =
				array_merge($this->html_head_entries, $entries);
	}

	// }}}
	// {{{ private function displayHtmlHeadEntries()

	private function displayHtmlHeadEntries()
	{
		$html_head_entries = $this->getHtmlHeadEntries();

		// sort array by display order
		usort($html_head_entries, array('SwatHtmlHeadEntry', 'compare'));

		foreach ($html_head_entries as $head_entry) {
			$head_entry->display();
			echo "\n";
		}
	}

	// }}}
}
?>
