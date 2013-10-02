<?php

require_once 'Site/Site.php';
require_once 'Site/SiteApplication.php';
require_once 'Site/SiteConfigModule.php';

class SiteRedisModuleTestApplication extends SiteApplication
{
	public function run()
	{
	}

	protected function getDefaultModuleList()
	{
		return array(
			'config' => 'SiteConfigModule',
		);
	}
}

?>
