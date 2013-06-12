<?php

require_once 'Site/pages/SitePageDecorator.php';
require_once 'Site/exceptions/SiteInvalidMacException.php';

/**
 * Page decorator that uses shared secret to check request authenticity
 *
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSharedSecretAuthPage extends SitePageDecorator
{
	// {{{ protected properties

	/**
	 * List of GET variables we exclude from the MAC check.
	 *
	 * @var array
	 */
	protected $exclude_names = array('mac', 'source', 'instance');

	// }}}
	// {{{ public init()

	public function init()
	{
		if (!$this->isRequestAuthentic($this->getVariables())) {
			$key     = $this->getHashKey();
			$message = $this->getHashMessage($this->getVariables());

			$expected = $this->getHashMac($message, $key);
			$provided = (isset($_GET['mac'])) ? $_GET['mac'] : '';

			throw new SiteInvalidMacException(sprintf(
				"Invalid message authentication code.\n\n".
				"Code expected: %s.\n".
				"Code provided: %s.", $expected, $provided));
		}

		parent::init();
	}

	// }}}
	// {{{ protected function getHashKey()

	protected function getHashKey()
	{
		$api_key = (isset($_GET['key'])) ? $_GET['key'] : '';

		if ($api_key == '') {
			throw new SiteInvalidMacException('No API key provided.');
		}

		$class_name = SwatDBClassMap::get('SiteApiCredential');
		$credentials = new $class_name();
		$credentials->setDatabase($this->app->db);

		if (!$credentials->loadByApiKey($api_key)) {
			throw new SiteInvalidMacException(sprintf(
				'Unable to load shared secret for API key: %s.', $api_key));
		}

		return $credentials->api_shared_secret;
	}

	// }}}
	// {{{ protected function getVariables()

	protected function getVariables()
	{
		$vars = array();

		$exclude_names   = $this->exclude_names;
		$exclude_names[] = $this->app->session->getSessionName();

		foreach ($_GET as $name => $value) {
			if (!in_array($name, $exclude_names)) {
				$vars[$name] = $value;
			}
		}

		return $vars;
	}

	// }}}
	// {{{ protected function isRequestAuthentic()

	protected function isRequestAuthentic($vars)
	{
		$key     = $this->getHashKey();
		$message = $this->getHashMessage($vars);

		return ((isset($_GET['mac'])) &&
			($this->getHashMac($message, $key) === $_GET['mac']));
	}

	// }}}
	// {{{ protected function getApiKey()

	protected function getApiKey()
	{
		$api_key = (isset($_GET['key'])) ? $_GET['key'] : '';

		if ($api_key == '') {
			throw new SiteInvalidMacException('No API key provided.');
		}

		return $api_key;
	}

	// }}}
	// {{{ protected function getHashMessage()

	protected function getHashMessage($vars)
	{
		// Sort the varaibles into alphabetical order.
		ksort($vars, SORT_STRING);

		$message = '';

		foreach ($vars as $name => $value) {
			$message.= $name.$value;
		}

		return $message;
	}

	// }}}
	// {{{ protected function getHashMac()

	protected function getHashMac($message, $key)
	{
		return hash_hmac('sha256', $message, $key);
	}

	// }}}
}

?>
