<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Site/dataobjects/SiteAd.php';


/**
 * Report page for Ads
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAdDetails extends AdminIndex
{
	// {{{ protected properties

	/**
	 * @var SiteAd
	 */
	protected $ad;

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/admin/components/Ad/details.xml';

	/**
	 * @var array
	 */
	protected $periods;

	// }}}}

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
			'day'      => Site::_('Day'),
			'week'     => Site::_('Week'),
			'two_week' => Site::_('2 Weeks'),
			'month'    => Site::_('Month'),
			'total'    => Site::_('Total'),
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
		$class_name = SwatDBClassMap::get('SiteAd');
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
		$help_note->title = sprintf(Site::_(
			'To track this ad, append the variable “%s=%s” to incoming links.'),
			SwatString::minimizeEntities('ad'),
			SwatString::minimizeEntities($this->ad->shortname));

		ob_start();
		echo Site::_('Examples:'), '<ul>';

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
		case 'referrer_period_view' :
			return $this->getRefererPeriodTableModel();
		}
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
	// {{{ private function buildNavBar()

	private function buildNavBar()
	{
		$this->navbar->addEntry(new SwatNavBarEntry($this->ad->title));
	}

	// }}}
}

?>
