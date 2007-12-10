<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'SwatDB/SwatDB.php';

require_once 'include/StoreConversionRateCellRenderer.php';

/**
 * Report page for Ad
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdIndex extends AdminIndex
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML(dirname(__FILE__).'/index.xml');
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$num = count($view->getSelection());

		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Ad/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf('select Ad.id, Ad.title, Ad.shortname,
				Ad.total_referrers, OrderCountByAdView.order_count,
				cast(OrderCountByAdView.conversion_rate as numeric(5,2))
			from Ad
				inner join OrderCountByAdView on OrderCountByAdView.ad = Ad.id
			order by %s',
			$this->getOrderByClause($view, 'createdate desc'));

		$rs = SwatDB::query($this->app->db, $sql);

		return $rs;
	}

	// }}}
}

?>
