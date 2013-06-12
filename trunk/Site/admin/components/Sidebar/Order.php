<?php

require_once 'Admin/pages/AdminDBOrder.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Site/SiteGadgetFactory.php';
require_once 'Site/dataobjects/SiteGadgetInstanceWrapper.php';

/**
 * Order tool for sidebar gadgets
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSidebarOrder extends AdminDBOrder
{
	// process phase
	// {{{ protected function saveIndex()

	protected function saveIndex($id, $index)
	{
		SwatDB::updateColumn($this->app->db, 'GadgetInstance',
			'integer:displayorder', $index, 'integer:id', array($id));

		if (isset($this->app->memcache)) {
			$this->app->memcache->delete('gadget_instances');
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		$frame = $this->ui->getWidget('order_frame');
		$frame->title = Site::_('Order Sidebar Gadgets');
		parent::buildInternal();
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$this->navbar->popEntry();
		$this->navbar->addEntry(new SwatNavBarEntry(
			Site::_('Order Sidebar Gadgets')));
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		$order_widget = $this->ui->getWidget('order');

		$sql = sprintf('select * from GadgetInstance
			where instance %s %s
			order by displayorder',
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'));

		$gadget_instances = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('SiteGadgetInstanceWrapper'));

		foreach ($gadget_instances as $gadget_instance) {
			$gadget = SiteGadgetFactory::get($this->app, $gadget_instance);
			$order_widget->addOption($gadget_instance->id, $gadget->getTitle());
		}

		$sql = sprintf('select sum(displayorder) from GadgetInstance
			where instance %s %s',
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'));

		$sum = SwatDB::queryOne($this->app->db, $sql, 'integer');
		$options_list = $this->ui->getWidget('options');
		$options_list->value = ($sum == 0) ? 'auto' : 'custom';
	}

	// }}}
}

?>
