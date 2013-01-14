<?php

require_once 'Site/pages/SitePage.php';
require_once 'Site/dataobjects/SiteSignOnToken.php';
require_once 'Site/dataobjects/SiteApiCredential.php';

/**
 * Simple API page that provides sign on tokens to third parties
 *
 * @package   Site
 * @copyright 2013 silverorange
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
		$response = $this->getJsonResponse(
			$this->getIdent(),
			$this->getVar('key'));

		$this->layout->startCapture('content');
		echo json_encode($response);
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function getVar()

	protected function getVar($name)
	{
		return SiteApplication::initVar($name, null,
			SiteApplication::VAR_GET);
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
		$credentials = new $class_name();
		$credentials->setDatabase($this->app->db);

		if (!$credentials->loadByApiKey($key)) {
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

		$response = $this->getSignOnToken($ident, $credentials);
		return $response;
	}

	// }}}
	// {{{ protected function getSignOnToken()

	protected function getSignOnToken($ident, SiteApiCredential $credentials)
	{
		$class_name = SwatDBClassMap::get('SiteSignOnToken');
		$sign_on_token = new $class_name();
		$sign_on_token->setDatabase($this->app->db);

		if (!$sign_on_token->loadByIdent($ident, $credentials)) {
			$sign_on_token->ident = $ident;
			$sign_on_token->api_credential = $credentials->id;
			$sign_on_token->token = uniqid();
			$sign_on_token->createdate = new SwatDate();
			$sign_on_token->createdate->toUTC();
			$sign_on_token->save();
		}

		return array(
			'status' => array(
				'code'    => 'ok',
				'message' => '',
			),
			'token'  => $sign_on_token->token,
		);
	}

	// }}}
}

?>
