<?php

require_once 'Swat/SwatDate.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/dataobjects/SiteAccount.php';
require_once 'Site/dataobjects/SiteApiCredential.php';
require_once 'Site/dataobjects/SiteApiSignOnToken.php';
require_once 'Site/exceptions/SiteApiSignOnException.php';

/**
 * Page for logging into an account via a sign-on token
 *
 * @package   Site
 * @copyright 2013-2016 silverorange
 * @see       SiteApiSignOnToken
 */
abstract class SiteApiSignOnPage extends SitePage
{
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
	// {{{ protected function getCredential()

	protected function getCredential($api_key)
	{
		$class_name = SwatDBClassMap::get('SiteApiCredential');
		$credential = new $class_name();
		$credential->setDatabase($this->app->db);

		if (!$credential->loadByApiKey($api_key)) {
			throw new SiteApiSignOnException(
				sprintf(
					'Unable to load credential with the “%s” API key.',
					$api_key
				)
			);
		}

		return $credential;
	}

	// }}}
	// {{{ protected function getToken()

	protected function getToken($ident, $token_string,
		SiteApiCredential $credential)
	{
		$class_name = SwatDBClassMap::get('SiteApiSignOnToken');
		$token = new $class_name();
		$token->setDatabase($this->app->db);

		if (!$token->loadByIdentAndToken($ident,
			$token_string, $credential)) {

			throw new SiteApiSignOnException(
				sprintf(
					'An API sign on token with the ident “%s” '.
					'and token “%s” does not exist.',
					$ident,
					$token_string
				)
			);
		} else {
			// Some browsers send a HEAD request followed by a GET request.
			// We don't want to delete the token until the GET request or else
			// the token will be prematurely deleted.
			if ($_SERVER['REQUEST_METHOD'] === 'GET') {
				$token->delete();
			}
		}

		return $token;
	}

	// }}}
}

?>
