<?php

require_once 'Site/pages/SitePage.php';
require_once 'Site/SitePath.php';

/**
 * @package   Site
 * @copyright 2005-2007 silverorange
 */
abstract class SitePathPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var SitePath
	 */
	protected $path;

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
	// {{{ public function setPath()

	/**
	 * Sets the path of this page
	 *
	 * @param SitePath $path
	 */
	public function setPath(SitePath $path)
	{
		$this->path = $path;
	}

	// }}}
}

?>
