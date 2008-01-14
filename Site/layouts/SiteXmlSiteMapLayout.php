<?php

require_once 'Site/layouts/SiteLayout.php';

/**
 * Layout for an XML site map
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteXmlSiteMapLayout extends SiteLayout
{
	// {{{ public function __construct()

	public function __construct($app, $filename = null)
	{
		parent::__construct($app, 'Site/layouts/xhtml/xmlsitemap.php');
	}

	// }}}
}

?>
