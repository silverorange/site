<?php

/**
 * Index page for sidebar gadgets
 *
 * @package   Site
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSidebarIndex extends AdminIndex
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->getUiXml());
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/index.xml';
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	public function processActions(SwatView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Sidebar/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf('select * from GadgetInstance
			where instance %s %s
			order by %s',
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->getOrderByClause($view, 'displayorder'));

		$gadget_instances = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('SiteGadgetInstanceWrapper'));

		$store = new SwatTableStore();
		foreach ($gadget_instances as $gadget_instance) {
			$gadget = SiteGadgetFactory::get($this->app, $gadget_instance);
			$ds = new SwatDetailsStore($gadget_instance);
			$ds->title = $gadget->getTitle();
			$store->add($ds);
		}

		$this->ui->getWidget('order_tool_link')->sensitive =
			(count($gadget_instances) > 1);

		return $store;
	}

	// }}}
}

?>
