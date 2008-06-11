<?php

require_once 'Swat/SwatPagination.php';

/**
 * A pagination widget for GET-based searches
 *
 * This pagination widget will automatically preserve HTTP GET variables
 * when generating links.  The current page is passed as a GET variable
 * named 'page'.  Optional a GET variable named 'type' will be used to
 * diffirentiate between two SiteSearchPagination widgets on the same
 * search results page.
 *
 * @package   Site
 * @copyright 2004-2007 silverorange
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
	 * Optional string to identify the type of content being paged with this widget
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
}

?>
