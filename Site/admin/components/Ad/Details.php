<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatMoneyCellRenderer.php';
require_once 'Swat/SwatNumericCellRenderer.php';
require_once 'Store/dataobjects/StoreAd.php';
require_once 'Store/dataobjects/StoreRegionWrapper.php';


/**
 * Report page for Ads
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdDetails extends AdminIndex
{
	// {{{ protected properties

	/**
	 * @var StoreAd
	 */
	protected $ad;

	protected $ui_xml = 'Store/admin/components/Ad/details.xml';

	protected $periods;

	// }}}}
	// {{{ private properties

	/**
	 * Cache of regions used by queryRegions()
	 *
	 * @var RegionsWrapper
	 */
	private $regions = null;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$id = SiteApplication::initVar('id');
		if (!$this->initAd($id)) {
			throw new AdminNotFoundException(
				sprintf('Ad with an id of ‘%s’ not found.', $id));
		}

		$this->ui->loadFromXML($this->ui_xml);
		$this->ui->getWidget('index_frame')->subtitle = $this->ad->title;

		$this->periods = array(
			'day'      => Store::_('Day'),
			'week'     => Store::_('Week'),
			'two_week' => Store::_('2 Weeks'),
			'month'    => Store::_('Month'),
			'total'    => Store::_('Total'),
		);
	}

	// }}}
	// {{{ protected function initAd()

	/**
	 * @var integer $id
	 *
	 * @return boolean
	 */
	protected function initAd($id)
	{
		$class_name = SwatDBClassMap::get('StoreAd');
		$this->ad = new $class_name();
		$this->ad->setDatabase($this->app->db);
		return $this->ad->load($id);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$help_note = $this->ui->getWidget('ad_tag_help');
		$help_note->title = sprintf(Store::_(
			'To track this ad, append the variable “ad=%s” to incoming links.'),
			SwatString::minimizeEntities($this->ad->shortname));

		ob_start();
		echo Store::_('Examples:'), '<ul>';

		$base_href = $this->app->getFrontendBaseHref();
		printf(
			'<li>%1$s<strong>?ad=%2$s</strong></li>'.
			'<li>%1$s?othervar=otherval<strong>&ad=%2$s</strong></li>'.
			'<li>%1$sus/en/category/product<strong>?ad=%2$s</strong></li>',
			SwatString::minimizeEntities($base_href),
			SwatString::minimizeEntities($this->ad->shortname));

		echo '</ul>';
		$help_note->content = ob_get_clean();
		$help_note->content_type = 'text/xml';

		$this->buildNavBar();
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		switch ($view->id) {
		case 'orders_view' :
			return $this->getOrdersTableModel();
		case 'referrer_period_view' :
			return $this->getRefererPeriodTableModel();
		}
	}

	// }}}
	// {{{ protected function getOrdersTableModel()

	protected function getOrdersTableModel()
	{
		$regions = $this->queryRegions();
		$this->appendRegionColumns($regions);

		$sql = sprintf('select * from RegionSalesByAdView where ad = %s',
			$this->app->db->quote($this->ad->id, 'integer'));

		$rs = SwatDB::query($this->app->db, $sql);

		$store = new SwatTableStore();

		foreach ($rs as $row) {
			foreach ($this->periods as $key => $value) {
				$periods[$key]->period = $value;

				$col1 = 'subtotal_'.$row->region;
				$col2 = $key.'_sales';
				$periods[$key]->$col1 = $row->$col2;

				$col1 = 'orders_'.$row->region;
				$col2 = $key.'_orders';
				$periods[$key]->$col1 = $row->$col2;

				$periods[$key]->total_orders += $row->$col2;
			}
		}

		foreach ($periods as $period)
			$store->add($period);

		return $store;
	}

	// }}}
	// {{{ protected function getRefererPeriodTableModel()

	protected function getRefererPeriodTableModel()
	{
		$sql = sprintf('select * from AdReferrerByPeriodView where ad = %s',
			$this->app->db->quote($this->ad->id, 'integer'));

		$row = SwatDB::queryRow($this->app->db, $sql);

		$store = new SwatTableStore();

		foreach ($this->periods as $key => $val) {
			$myvar->period = $val;
			$myvar->referrers = intval($row->$key);

			$store->add(clone $myvar);
		}

		return $store;
	}

	// }}}
	// {{{ protected function appendRegionColumns()

	protected function appendRegionColumns($regions)
	{
		$view = $this->ui->getWidget('orders_view');

		foreach ($regions as $region) {
			$subtotal_column = new SwatTableViewColumn('subtotal_'.$region->id);
			$subtotal_column->title = sprintf(Store::_('%s Subtotal'),
				$region->title);

			$subtotal_renderer = new SwatMoneyCellRenderer();
			$subtotal_renderer->locale = $region->getFirstLocale()->id;

			$subtotal_column->addRenderer($subtotal_renderer);
			$subtotal_column->addMappingToRenderer($subtotal_renderer,
				'subtotal_'.$region->id, 'value');


			$orders_column = new SwatTableViewColumn('orders_'.$region->id);
			$orders_column->title = sprintf(Store::_('%s Orders'),
				$region->title);

			$orders_renderer = new SwatNumericCellRenderer();

			$orders_column->addRenderer($orders_renderer);
			$orders_column->addMappingToRenderer($orders_renderer,
				'orders_'.$region->id, 'value');

			$view->appendColumn($orders_column);
			$view->appendColumn($subtotal_column);
		}

		$orders_column = new SwatTableViewColumn('total_orders');
		$orders_column->title = Store::_('Total Orders');

		$orders_renderer = new SwatNumericCellRenderer();

		$orders_column->addRenderer($orders_renderer);
		$orders_column->addMappingToRenderer($orders_renderer,
			'total_orders', 'value');

		$view->appendColumn($orders_column);

	}
	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar()
	{
		$this->navbar->addEntry(new SwatNavBarEntry($this->ad->title));
	}

	// }}}
	// {{{ private function queryRegions()

	private function queryRegions()
	{
		if ($this->regions === null) {
			$sql = 'select id, title from Region order by Region.id';

			$this->regions =
				SwatDB::query($this->app->db, $sql,
					SwatDBClassMap::get('StoreRegionWrapper'));
		}

		return $this->regions;
	}

	// }}}
}

?>
