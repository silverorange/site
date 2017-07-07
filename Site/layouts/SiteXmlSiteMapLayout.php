<?php

/**
 * Layout for an XML site map
 *
 * @package   Site
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteXmlSiteMapLayout extends SiteLayout
{
	// {{{ public function __construct()

	public function __construct($app, $template_class = null)
	{
		parent::__construct($app, SiteXMLSiteMapTemplate::class);
	}

	// }}}
}

?>
