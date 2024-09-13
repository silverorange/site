<?php

/**
 * Config page used for displaying and saving config settings for the Site
 * package.
 *
 * @package   Site
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteConfigPage extends SiteAbstractConfigPage
{
	// {{{ public function getPageTitle()

	public function getPageTitle()
	{
		return 'Site Settings';
	}

	// }}}
	// {{{ public function getConfigSettings()

	public function getConfigSettings()
	{
		return ['site' => ['title', 'meta_description'], 'date' => ['time_zone']];
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/config-page.xml';
	}

	// }}}
}

?>
