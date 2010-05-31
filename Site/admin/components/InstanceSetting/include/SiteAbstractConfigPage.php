<?php

require_once 'Swat/SwatUI.php';

/**
 * Abstract page that initilizes, loads and saves a user interface based on
 * current configuration settings.
 *
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteAbstractConfigPage
{
	// {{{ protected properties

	/**
	 * @var SwatUI
	 */
	protected $ui;

	// }}}
	// {{{ public function getUI()

	public function getUI()
	{
		return $this->ui;
	}

	// }}}
	// {{{ public function initUI()

	public function initUI()
	{
		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->getPathToXML());

		foreach ($this->getConfigSettings() as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;
				$widget = $this->ui->getWidget($field_name);
				$method = 'init'.str_replace(' ', '',
					ucwords(str_replace('_', ' ', $field_name)));

				if (method_exists($this, $method)) {
					$this->$method($widget);
				}
			}
		}

		$this->ui->init();
	}

	// }}}
	// {{{ public function saveUI()

	public function saveUI(SiteConfigModule $config)
	{
		$changed_settings = array();

		foreach ($this->getConfigSettings() as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;
				$widget = $this->ui->getWidget($field_name);
				$method = 'save'.str_replace(' ', '',
					ucwords(str_replace('_', ' ', $field_name)));

				if (method_exists($this, $method)) {
					if ($this->$method($config, $widget)) {
						$changed_settings[] = $section.'.'.$name;
					}
				} else if ($config->$section->$name !== $widget->value) {
					$config->$section->$name = $widget->value;
					$changed_settings[] = $section.'.'.$name;
				}
			}
		}

		return $changed_settings;
	}

	// }}}
	// {{{ public function loadUI()

	public function loadUI(SiteConfigModule $config)
	{
		foreach ($this->getConfigSettings() as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;
				$widget = $this->ui->getWidget($field_name);
				$method = 'load'.str_replace(' ', '',
					ucwords(str_replace('_', ' ', $field_name)));

				if (method_exists($this, $method)) {
					$this->$method($config, $widget);
				} else {
					$widget->value = $config->$section->$name;
				}
			}
		}
	}

	// }}}
	// {{{ abstract public function getPageTitle()

	abstract public function getPageTitle();

	// }}}
	// {{{ abstract public function getConfigSettings()

	abstract public function getConfigSettings();

	// }}}
	// {{{ abstract protected function getUiXml()

	abstract protected function getUiXml();

	// }}}
}

?>
