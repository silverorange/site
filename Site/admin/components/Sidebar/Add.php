<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Site/SiteGadgetFactory.php';
require_once 'Site/dataobjects/SiteGadgetInstance.php';

/**
 * Page for adding a sidebar gadget
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSidebarAdd extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var SiteGadgetInstance
	 */
	protected $gadget_instance;

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/admin/components/Sidebar/add.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initGadgetInstance();
		$this->initGadgetList();
	}

	// }}}
	// {{{ protected function initGadgetInstance()

	protected function initGadgetInstance()
	{
		$class_name = SwatDBClassMap::get('SiteGadgetInstance');
		$this->gadget_instance = new $class_name();
		$this->gadget_instance->setDatabase($this->app->db);
	}

	// }}}
	// {{{ protected function initGadgetList()

	protected function initGadgetList()
	{
		$radio_list = $this->ui->getWidget('gadget');
		$available = SiteGadgetFactory::getAvailable($this->app);

		$options = array();

		foreach ($available as $gadget_class => $gadget) {
			$option_title = SwatString::minimizeEntities($gadget->title);
			if ($gadget->description !== null) {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->class = 'swat-note';
				$span_tag->setContent($gadget->description);
				$option_title.='<br />'.$span_tag;
			}

			$options[$gadget_class] = $option_title;
		}

		asort($options, SORT_LOCALE_STRING);

		foreach ($options as $gadget_class => $option_title)
			$radio_list->addOption($gadget_class, $option_title,
				'text/xml');
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$gadget_class = $this->ui->getWidget('gadget')->value;
		$this->gadget_instance->gadget   = $gadget_class;
		$this->gadget_instance->instance = $this->app->getInstanceId();
		$this->gadget_instance->save();

		$gadget = SiteGadgetFactory::get($this->app, $this->gadget_instance);

		$message = new SwatMessage(sprintf(
			Site::_('“%s” has been added to the sidebar.'),
			$gadget->getTitle()));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$uri = sprintf('Sidebar/Settings?id=%s',
			$this->gadget_instance->id);

		$this->app->relocate($uri);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$this->navbar->popEntry();
		$this->navbar->createEntry(Site::_('Add Sidebar Gadget'));
	}

	// }}}
	// {{{ protected function buildFrame()

	protected function buildFrame()
	{
	}

	// }}}
	// {{{ protected function buildButton()

	protected function buildButton()
	{
	}

	// }}}
}

?>
