<?php

require_once 'Site/SiteApplicationModule.php';

/**
 * A server configuration module.
 *
 * @package   Site
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteServerConfigModule extends SiteApplicationModule
{
	// {{{ public properties

	public $pear_data_dir = '/usr/share/pear/data';

	public $config_file = null;

	// }}}
	// {{{ protected properties

	protected $config = null;

	// }}}

	// }}}

	// {{{ public function init()

	public function init()
	{
		if ($this->config_file === null)
			$this->config_file = $this->pear_data_dir.'/Site/config.ini';
	}

	// }}}
    // {{{ public function __get()

	/**
	 * Get a configuration setting.
	 */
	public function __get($name)
	{
		if ($this->config === null)
			$this->loadConfig();

		if (!isset($this->config[$name]))
			throw new StoreException("Configuration setting '$name' is not set.");

		return $this->config[$name];
	}

    // }}}
    // {{{ private function loadConfig()

	private function loadConfig()
	{
		$this->config = array();

		foreach (file($this->config_file) as $line)
			if (ereg('(.*)=(.*)', $line, $regs))
				$this->config[$regs[1]] = $regs[2];
	}

    // }}}

}

?>
