<?php

require_once 'Admin/pages/AdminPage.php';
require_once 'Site/admin/SiteThemeDisplay.php';
require_once 'SwatDB/exceptions/SwatDBException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBTransaction.php';

/**
 * Page for selecting a theme
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteThemeIndex extends AdminPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/admin/components/Theme/index.xml';

	/**
	 * @var array
	 */
	protected $themes;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initThemes();
		$this->initThemeReplicator();
	}

	// }}}
	// {{{ protected function initThemes()

	protected function initThemes()
	{
		$current_theme = $this->app->config->site->theme;
		$themes = $this->app->theme->getAvailable();

		// sorts themes by title according to locale
		$titles = array();
		foreach ($themes as $theme) {
			if ($theme->getShortname() !== $current_theme) {
				$titles[$theme->getShortname()] = $theme->getTitle();
			}
		}

		asort($titles, SORT_LOCALE_STRING);

		// current theme is always placed at the top
		$this->themes = array(
			$current_theme => $themes[$current_theme]
		);

		foreach ($titles as $shortname => $title)
			$this->themes[$shortname] = $themes[$shortname];
	}

	// }}}
	// {{{ protected function initThemeReplicator()

	protected function initThemeReplicator()
	{
		$theme_replicator = $this->ui->getWidget('theme_replicator');
		$theme_replicator->replication_ids = array_keys($this->themes);
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$form = $this->ui->getWidget('form');

		if ($form->isProcessed()) {
			$theme_replicator = $this->ui->getWidget('theme_replicator');
			foreach ($theme_replicator->replication_ids as $shortname) {
				$theme_display = $theme_replicator->getWidget('theme',
					$shortname);

				if ($theme_display->hasBeenClicked()) {
					$this->updateTheme($shortname);
					$this->relocate();
				}
			}
		}
	}

	// }}}
	// {{{ protected function updateTheme()

	protected function updateTheme($shortname)
	{
		$theme = $this->themes[$shortname];

		$transaction = new SwatDBTransaction($this->app->db);
		try {
			$sql = sprintf('delete from InstanceConfigSetting where name = %s
				and instance = %s',
				$this->app->db->quote('site.theme', 'text'),
				$this->app->db->quote($this->app->getInstanceId(), 'integer'));

			SwatDB::exec($this->app->db, $sql);

			$sql = sprintf('insert into InstanceConfigSetting
				(name, value, instance) values (%s, %s, %s)',
				$this->app->db->quote('site.theme', 'text'),
				$this->app->db->quote($shortname, 'text'),
				$this->app->db->quote($this->app->getInstanceId(), 'integer'));

			SwatDB::exec($this->app->db, $sql);

			$transaction->commit();
		} catch (SwatDBException $e) {
			$transaction->rollback();
			throw $e;
		}

		$message = new SwatMessage(sprintf(
			Site::_('The theme “%s” has been selected.'),
			$theme->getTitle()));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function relocate()

	/**
	 * Relocate after process
	 */
	protected function relocate()
	{
		$this->app->relocate($this->source);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildMessages();
		$this->buildForm();
		$this->buildThemeReplicator();
	}

	// }}}
	// {{{ protected function buildThemeReplicator()

	protected function buildThemeReplicator()
	{
		$current_theme = $this->app->config->site->theme;
		$theme_replicator = $this->ui->getWidget('theme_replicator');
		foreach ($theme_replicator->replication_ids as $shortname) {
			$theme_display = $theme_replicator->getWidget('theme', $shortname);
			$theme_display->setTheme($this->themes[$shortname]);
			$theme_display->selected = ($shortname == $current_theme);
		}
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		$form = $this->ui->getWidget('form');
		$form->action = $this->source;
	}

	// }}}
}

?>
