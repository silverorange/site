<?php

/**
 * Simple API page that provides sign on tokens to third parties
 *
 * @package   Site
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteApiGetTokenPage extends SitePage
{
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout($this->app, 'Site/layouts/xhtml/json.php');
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		// Instruct shared proxies, like varnish, to not cache this response
		header('Cache-Control: s-maxage=0, must-revalidate');

		$response = $this->getJsonResponse(
			$this->getIdent(),
			$this->getVar('key')
		);

		$this->layout->startCapture('content');
		echo json_encode($response);
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function getVar()

	protected function getVar($name)
	{
		return SiteApplication::initVar(
			$name,
			null,
			SiteApplication::VAR_GET
		);
	}

	// }}}
	// {{{ protected function getIdent()

	protected function getIdent()
	{
		return $this->getVar('id');
	}

	// }}}
	// {{{ protected function getJsonResponse()

	protected function getJsonResponse($ident, $key)
	{
		$class_name = SwatDBClassMap::get('SiteApiCredential');
		$credential = new $class_name();
		$credential->setDatabase($this->app->db);

		if (!$credential->loadByApiKey($key)) {
			return array(
				'status' => array(
					'code'    => 'error',
					'message' => 'Invalid API key provided.',
				),
			);
		}

		if ($ident == '') {
			return array(
				'status' => array(
					'code'    => 'error',
					'message' => 'Invalid unique identifier provided.',
				),
			);
		}

		$response = $this->getSignOnToken($ident, $credential);
		return $response;
	}

	// }}}
	// {{{ protected function getSignOnToken()

	protected function getSignOnToken($ident, SiteApiCredential $credential)
	{
		$class_name = SwatDBClassMap::get('SiteApiSignOnToken');
		$token = new $class_name();
		$token->setDatabase($this->app->db);

		if (!$token->loadByIdent($ident, $credential)) {
			$token->ident = $ident;
			$token->api_credential = $credential->id;
			$token->token = uniqid();
			$token->createdate = new SwatDate();
			$token->createdate->toUTC();
			$token->save();
		}

		return array(
			'status' => array(
				'code'    => 'ok',
				'message' => '',
			),
			'token'  => $token->token,
		);
	}

	// }}}
}

?>
