<?php

require_once 'Site/pages/SitePage.php';

/**
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteJSONPage extends SitePage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		array $arguments = array())
	{
		parent::__construct($app, $layout, $arguments);

		$this->setLayout(
			new SiteLayout(
				$this->app,
				'Site/layouts/xhtml/json.php'
			)
		);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->layout->data->content = json_encode($this->getResponse());
	}

	// }}}
	// {{{ abstract protected function getResponse()

	abstract protected function getResponse();

	// }}}
	// {{{ protected function getStatus()

	protected function getStatus($code = 'ok', $message = '')
	{
		return array(
			'code'    => $code,
			'message' => $message,
		);
	}

	// }}}
}

?>
