<?php

require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Site/layouts/SiteXMLRPCServerLayout.php';
require_once 'Site/SitePageFactory.php';

/**
 * @package   Site
 * @copyright 2006-2008 silverorange
 */
class SiteXMLRPCServerFactory extends SitePageFactory
{
	// {{{ public function get()

	public function ($source, SiteLayout $layout = null)
	{
		$layout = ($layout === null) ? $this->getLayout($source) : $layout;
		$map = $this->getPageMap();

		if (!isset($map[$source])) {
			throw new SiteNotFoundException();
		}

		$class = $map[$source];
		return $this->getPage($class, $layout);
	}

	// }}}
	// {{{ protected function getPageMap()

	protected function getPageMap()
	{
		return array(
			'upload-status' => 'SiteUploadStatusServer',
		);
	}

	// }}}
	// {{{ protected function getLayout()

	protected function getLayout($source)
	{
		return new SiteXMLRPCServerLayout($this->app);
	}

	// }}}
}

?>
