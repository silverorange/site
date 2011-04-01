<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Swat/SwatNoteBookPage.php';
require_once 'Swat/SwatMessage.php';
require_once dirname(__FILE__).'/include/SiteConfigPage.php';

/**
 * Main page used to edit instance configuration settings.
 *
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteInstanceSettingIndex extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * The location of the XML file for the main user interface.
	 *
	 * @var string
	 */
	protected $ui_xml = 'Site/admin/components/InstanceSetting/index.xml';

	/**
	 * An array containing each config page that will be linked into the main
	 * user interface.
	 *
	 * @var array
	 */
	protected $config_pages = array();

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		if (!$this->app->hasModule('SiteMultipleInstanceModule')) {
			$text = Site::_(
				'Only sites with multiple instances can use this component.');

			throw new AdminNotFoundException($text);
		}

		// set the id so that the loadDB() method is called
		$this->id = 1;
		$this->initConfigPages();

		// Link all the page UIs into the main UI tree
		foreach ($this->config_pages as $config_page) {
			$config_page->initUI();
			$notebook_page = new SwatNoteBookPage();
			$notebook_page->title = $config_page->getPageTitle();
			$notebook_page->addChild($config_page->getUi()->getRoot());
			$this->ui->getWidget('edit_notebook')->addPage($notebook_page);
		}
	}

	// }}}
	// {{{ protected function initConfigPages()

	protected function initConfigPages()
	{
		$this->config_pages[] = new SiteConfigPage();
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$changed_settings = array();

		if ($this->ui->getWidget('submit_button')->hasBeenClicked()) {
			foreach ($this->config_pages as $config_page) {
				$changed_settings = array_merge($changed_settings,
					$config_page->saveUi($this->app->config));
			}

			if (count($changed_settings > 0)) {
				$this->app->config->save($changed_settings);
				$this->app->messages->add($this->getSavedMessage());
			}
		} else if ($this->ui->getWidget('default_button')->hasBeenClicked()) {
			$instance_id = $this->app->getInstanceId();

			if ($instance_id !== null) {
				$sql = sprintf('delete from InstanceConfigSetting
					where instance = %s and is_default = %s',
					$this->app->db->quote($instance_id, 'integer'),
					$this->app->db->quote(false, 'boolean'));

				SwatDB::exec($this->app->db, $sql);

				$this->app->messages->add($this->getRestoredMessage());
			}
		}
	}

	// }}}
	// {{{ protected function getSavedMessage()

	protected function getSavedMessage()
	{
		return new SwatMessage(
			Site::_(
				'Instance configuration settings have been updated.'
			)
		);
	}

	// }}}
	// {{{ protected function getRestoredMessage()

	protected function getRestoredMessage()
	{
		$message = new SwatMessage(
			Site::_(
				'Instance configuration settings have been restored to '.
				'defaults.'
			)
		);

		$message->secondary_content = Site::_(
			'Reload this page to see your changes.'
		);

		return $message;
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		// We want to stay on the instance config page after processing
	}

	// }}}

	// build phase
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		// We don't want to add any extra entries to the nav-bar
	}

	// }}}
	// {{{ protected function buildFrame()

	protected function buildFrame()
	{
		// We don't want to alter the frames title.
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		foreach ($this->config_pages as $config_page) {
			$config_page->loadUi($this->app->config);
		}
	}

	// }}}
}

?>
