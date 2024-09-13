<?php

/**
 * Application to process queued SiteCdnTasks using SiteAmazonCdnModule
 *
 * @package   Site
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAmazonCdnUpdater extends SiteCdnUpdater
{
	// boilerplate code


	protected function configure(SiteConfigModule $config)
	{
		parent::configure($config);

		$this->cdn->bucket            = $config->amazon->bucket;
		$this->cdn->access_key_id     = $config->amazon->access_key_id;
		$this->cdn->access_key_secret = $config->amazon->access_key_secret;

		if ($config->amazon->reduced_redundancy) {
			$this->cdn->setReducedRedundancy();
		}
	}




	protected function getDefaultModuleList()
	{
		return array_merge(
			parent::getDefaultModuleList(),
			[
				'cdn' => SiteAmazonCdnModule::class,
			]
		);
	}


}

?>
