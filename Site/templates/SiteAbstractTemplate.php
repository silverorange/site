<?php

/**
 * @package   Site
 * @copyright 2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteAbstractTemplate implements SiteTemplateInterface
{


	public function asString(SiteLayoutData $data)
	{
		ob_start();
		$this->display($data);
		return ob_get_clean();
	}


	public function getBaseUri()
	{
		$uri = $_SERVER['HTTP_HOST'];
		$is_stage = ( mb_strpos($uri, "berna.silverorange.com") !== false );
		$stage_uri = 'https://'.$_SERVER['HTTP_HOST'].
			mb_substr($_SERVER['REQUEST_URI'], 0, mb_strpos($_SERVER['REQUEST_URI'], "www") + 3);
		return $is_stage
			? $stage_uri
			: 'https://www.emrap.org';
	}


}

?>
