<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatString.php';
require_once 'Site/pages/SitePageDecorator.php';
require_once 'Site/SitePath.php';

/**
 * Path page decorator
 *
 * @package   Site
 * @copyright 2004-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SitePathPage extends SitePageDecorator
{
	// {{{ protected properties

	/**
	 * @var SitePath
	 *
	 * @see SitePathPage::getPath()
	 * @see SitePathPage::setPath()
	 */
	protected $path;

	// }}}
	// {{{ public function setPath()

	/**
	 * Sets the path of this page
	 *
	 * Note: Ideally, the path would be set in the constructor of this class
	 * and would only have a public accessor method. A setter method exists
	 * here for backwards compatibility.
	 *
	 * @param SitePath $path
	 */
	public function setPath(SitePath $path)
	{
		$this->path = $path;
	}

	// }}}
	// {{{ public function getPath()

	/**
	 * Gets the path of this page
	 *
	 * @return SitePath the path of this page.
	 */
	public function getPath()
	{
		return $this->path;
	}

	// }}}
	// {{{ public function hasParentInPath()

	/**
	 * Whether or not this page has the parent id in its path
	 *
	 * @param integer $id the parent id to check.
	 *
	 * @return boolean true if this page has the given id in its path and false
	 *                  if it does not.
	 */
	public function hasParentInPath($id)
	{
		return $this->path->hasId($id);
	}

	// }}}

	// build phase
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$navbar = $this->layout->navbar;
		$link = '';
		$first = true;
		foreach ($this->path as $path_entry) {
			if ($first) {
				$link.= $path_entry->shortname;
				$first = false;
			} else {
				$link.= '/'.$path_entry->shortname;
			}

			$navbar->createEntry($path_entry->title, $link);
		}
	}

	// }}}
}

?>
