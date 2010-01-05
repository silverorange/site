<?php

require_once 'Swat/SwatPagination.php';

/**
 * A pagination widget for GET-based searches
 *
 * This pagination widget will automatically preserve HTTP GET variables
 * when generating links.  The current page is passed as a GET variable
 * named 'page'.  Optional a GET variable named 'type' will be used to
 * differentiate between two SiteSearchPagination widgets on the same
 * search results page.
 *
 * This widget also supports pagination of result sets where the total count is
 * only known accurately up to a certain point.
 *
 * @package   Site
 * @copyright 2004-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSearchPagination extends SwatPagination
{
	// {{{ public properties

	/**
	 * HTTP GET varables that are not to be preserved
	 *
	 * @var array
	 */
	public $unset_get_vars = array();

	/**
	 * @var integer
	 */
	public $max_accurate_records;

	/**
	 * Optional string to identify the type of content being paged with this
	 * widget
	 *
	 * @var string
	 */
	public $type;

	// }}}
	// {{{ public function process()

	/**
	 * Processes this pagination widget
	 *
	 * Sets the current_page and current_record properties.
	 */
	public function process()
	{
		parent::process();

		if ($this->type !== null &&	isset($_GET['type'])) {
			if ($_GET['type'] === $this->type && isset($_GET['page']))
				$this->setCurrentPage($_GET['page']);
		} else {
			if (isset($_GET['page']))
				$this->setCurrentPage($_GET['page']);
		}
	}

	// }}}
	// {{{ protected function getLink()

	/**
	 * Gets the base link for all page links
	 *
	 * This removes all unwanted variables from the current HTTP GET variables
	 * and adds all wanted variables ones back into the link string.
	 *
	 * @return string the base link for all pages with cleaned HTTP GET
	 *                 variables.
	 */
	protected function getLink()
	{
		$vars = $_GET;

		$this->unset_get_vars[] = 'source';
		$this->unset_get_vars[] = 'page';
		$this->unset_get_vars[] = 'type';
		$this->unset_get_vars[] = 'instance';

		foreach($vars as $name => $value)
			if (in_array($name, $this->unset_get_vars))
				unset($vars[$name]);

		if ($this->link === null)
			$link = '?';
		else
			$link = $this->link.'?';

		foreach($vars as $name => $value) {
			if (is_array($value)) {
				foreach ($value as $sub_value)
					$link .= $name.'[]='.urlencode($sub_value).'&';
			} elseif ($value != '') {
				$link .= $name.'='.urlencode($value).'&';
			}
		}

		if ($this->type !== null)
			$link.= sprintf('type=%s&', $this->type);

		$link = str_replace('%', '%%', $link);
		$link.= 'page=%s';

		return $link;
	}

	// }}}
	// {{{ protected function displayPages()

	/**
	 * Displays a smart list of pages
	 */
	protected function displayPages()
	{
		$j = 0;

		$link = $this->getLink();

		$anchor = new SwatHtmlTag('a');
		$span = new SwatHtmlTag('span');
		$current = new SwatHtmlTag('span');
		$current->class = 'swat-pagination-current';

		$total_pages = max($this->total_pages, $this->current_page);

		for ($i = 1; $i <= $total_pages; $i++) {
			$display = false;

			if ($this->current_page < 7 && $i <= 10) {
				// Current page is in the first 6, show the first 10 pages
				$display = true;

			} elseif ($this->current_page > $this->total_pages - 6 &&
				$i >= $this->total_pages - 10 &&
				($this->max_accurate_records === null ||
				$this->total_records <= $this->max_accurate_records)) {

				// Current page is in the last 6, show the last 10 pages
				$display = true;

			} elseif ($i < 3 ||
				($i > $this->total_pages - 2 &&
				($this->max_accurate_records === null ||
				$this->total_records <= $this->max_accurate_records)) ||
				abs($this->current_page - $i) <= 3) {

				// Always show the first 2, last 2, and middle 6 pages
				$display = true;
			} elseif ($this->max_accurate_records !== null &&
				$total_pages > $this->total_pages &&
				$this->current_page - $i <= min(10, 3 + $total_pages - $this->total_pages)) {

				// When total records are unknown, grow the last number of
				// pages until 10 are displayed.
				$display = true;
			}

			if ($display) {
				if ($j + 1 != $i) {
					// ellipses
					$span->setContent('…');
					$span->display();
				}

				if ($i == $this->current_page) {
					$current->setContent((string)$i);
					$current->display();
				} else {
					$anchor->href = sprintf($link, (string)$i);
					$anchor->title =
						sprintf(Swat::_('Go to page %d'), ($i));

					$anchor->setContent((string)($i));
					$anchor->display();
				}

				$j = $i;
			}
		}

		if ($this->max_accurate_records !== null &&
			$this->total_records > $this->max_accurate_records) {
			// ellipses
			$span->setContent('…');
			$span->display();
		}
	}

	// }}}
	// {{{ protected function calculatePages()

	/**
	 * Calculates page totals
	 *
	 * Sets the internal total_pages, next_page and prev_page properties.
	 */
	protected function calculatePages()
	{

		if ($this->max_accurate_records === null) {
			$records = $this->total_records;
		} else {
			$records = min($this->max_accurate_records, $this->total_records);
		}

		$this->total_pages = ceil($records / $this->page_size);

		if (($this->total_pages <= 1) ||
			($this->total_pages == $this->current_page &&
			($this->max_accurate_records === null ||
			$this->total_records <= $this->max_accurate_records))) {
			$this->next_page = 0;
		} else {
			$this->next_page = $this->current_page + 1;
		}

		if ($this->current_page > 0) {
			$this->prev_page = $this->current_page - 1;
		} else {
			$this->prev_page = 0;
		}
	}

	// }}}
}

?>
