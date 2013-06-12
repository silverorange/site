<?php

require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteSessionModule.php';
require_once 'Site/dataobjects/SiteAccountLoginSessionWrapper.php';

/**
 * Cleans up dead SiteAccountLoginSession entries in the database.
 *
 * Any entries in the database that don't have a tag (used for persistent
 * logins) or a corresponding session file on disk are removed.
 *
 * @package   Site
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */

class SiteAccountLoginSessionCleaner extends SiteCommandLineApplication
{
	// {{{ public function run()

	public function run()
	{
		parent::run();

		$this->debug("Cleaning out dead sessions...\n\n", true);

		$sessions = $this->getSessions();
		$locale   = SwatI18NLocale::get();

		$this->debug(
			sprintf(
				ngettext(
					'Found %s session... ',
					'Found %s sessions... ',
					count($sessions)
				),
				$locale->formatNumber(count($sessions))
			)
		);

		$count = 0;
		foreach ($sessions as $session) {
			if (!$this->session->sessionFileExists($session->session_id)) {
//				$session->delete();
				$count++;
			}
		}

		$this->debug(
			sprintf(
				"deleted %s.\n\n",
				$locale->formatNumber($count)
			)
		);

		$this->debug("All Done.\n\n", true);
	}

	// }}}
	// {{{ protected function getSessions()

	protected function getSessions()
	{
		$sql = 'select * from AccountLoginSession where tag %s %s';
		$sql = sprintf(
			$sql,
			SwatDB::equalityOperator(null),
			$this->db->quote(null)
		);

		return SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('SiteAccountLoginSessionWrapper')
		);
	}

	// }}}

	// boilerplate
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this search indexer
	 *
	 * @return array the list of modules to load for this application.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'SiteConfigModule',
			'database' => 'SiteDatabaseModule',
			'session'  => 'SiteSessionModule',
		);
	}

	// }}}
	// {{{ protected function addConfigDefinitions()

	/**
	 * Adds configuration definitions to the config module of this application
	 *
	 * @param SiteConfigModule $config the config module of this application to
	 *                                  witch to add the config definitions.
	 */
	protected function addConfigDefinitions(SiteConfigModule $config)
	{
		parent::addConfigDefinitions($config);
	}

	// }}}
	// {{{ protected function configure()

	protected function configure(SiteConfigModule $config)
	{
		parent::configure($config);
		$this->database->dsn = $config->database->dsn;
	}

}

?>