<?php

require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Site/layouts/SiteXMLRPCServerLayout.php';
require_once 'Site/SitePageFactory.php';

/**
 * @package   Site
 * @copyright 2006 silverorange
 */
abstract class SiteXMLRPCServerFactory extends SitePageFactory
{
	// {{{ public function resolvePage()

	public function resolvePage(SiteWebApplication $app, $source)
	{
		$layout = $this->resolveLayout($app, $source);
		$map = $this->getPageMap();

		if (isset($map[$source])) {
			$class = $map[$source];
			$params = array($app, $layout);
			$page = $this->instantiatePage($app, $class, $params);
			return $page;
		}

		throw new SiteNotFoundException();
	}

	// }}}
	// {{{ protected function resolveLayout()

	protected function resolveLayout($app, $source)
	{
		return new SiteXMLRPCServerLayout($app);
	}

	// }}}
}

?>
