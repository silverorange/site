<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * Web application module for running Gearman tasks
 *
 * @package   Site
 * @copyright 2013-2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteGearmanModule extends SiteApplicationModule
{
	// {{{ protected properties

	/**
	 * Gearman client used to communicate with server
	 *
	 * @var GearmanClient
	 */
	protected $client;

	// }}}
	// {{{ public function init()

	public function init()
	{
		// no initialization required
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The gearman module depends on the SiteConfigModule feature.
	 *
	 * @return array an array of {@link SiteApplicationModuleDependency}
	 *                        objects defining the features this module
	 *                        depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();
		$depends[] = new SiteApplicationModuleDependency('SiteConfigModule');
		return $depends;
	}

	// }}}
	// {{{ public function __call()

	/**
	 * Passes calls to the SiteGearmanModule to the GearmanClient
	 *
	 * @param string $name      method name being called.
	 * @param array  $arguments method arguments.
	 *
	 * @return mixed the method return value from GearmanClient.
	 */
	public function __call($name, $arguments)
	{
		$this->connect();

		// not checking class instance in case pecl-gearman not installed
		if ($this->client === null) {
			throw new SiteException(
				Site::_(
					'Gearman module was used but pecl-gearman not available.'
				)
			);
		}

		return call_user_func_array(array($this->client, $name), $arguments);
	}

	// }}}
	// {{{ protected function connect()

	/**
	 * Lazily creates a GearmanClient and connects to gearmand servers
	 */
	protected function connect()
	{
		if ($this->client === null && class_exists('GearmanClient')) {
			$this->client = new GearmanClient();
			$config = $this->app->getModule('SiteConfigModule');
			foreach (explode(',', $config->gearman->servers) as $server) {
				$server_parts = explode(':', trim($server), 2);
				$address = $server_parts[0];
				$port = (count($server_parts) === 2) ? $server_parts[1] : 4730;
				$this->client->addServer($address, $port);
			}
		}
	}

	// }}}
}

?>
