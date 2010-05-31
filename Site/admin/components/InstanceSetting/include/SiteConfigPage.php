<?php

require_once dirname(__FILE__).'/SiteAbstractConfigPage.php';

/**
 * Config page used for displaying and saving config settings for the Site
 * package.
 *
 * @package   Site
 * @copyright 2009 silverorange
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
		return array(
			'site' => array(
				'title',
				'meta_description',
			),
			'comment' => array(
				'akismet_key',
			),
			'date' => array(
				'time_zone',
			),
		);
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return dirname(__FILE__).'/config-page.xml';
	}

	// }}}
}

?>
