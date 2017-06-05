<?php

require_once __DIR__ . '/vendor/autoload.php';

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
