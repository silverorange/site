<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Admin/pages/AdminDBDelete.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Site/SiteGadgetFactory.php';
require_once 'Site/dataobjects/SiteGadgetInstanceWrapper.php';

/**
 * Delete confirmation page for sidebar gadgets
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSidebarDelete extends AdminDBDelete
{
	// init phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = sprintf('delete from GadgetInstance
			where id in (%s) and instance %s %s',
			$this->getItemList('integer'),
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'));

		$num = SwatDB::exec($this->app->db, $sql);

		if ($num > 0 && isset($this->app->memcache)) {
			$this->app->memcache->delete('gadget_instances');
		}

		$locale = SwatI18NLocale::get();
		$message = new SwatMessage(sprintf(Site::ngettext(
			'One gadget has been removed from the sidebar.',
			'%s gadgets have been removed from the sidebar.', $num),
			$locale->formatNumber($num)));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$yes_button = $this->ui->getWidget('yes_button');
		$yes_button->title = Site::_('Remove');

		$this->navbar->popEntry();
		$this->navbar->createEntry(Site::_('Remove'));

		$sql = sprintf('select id, gadget from GadgetInstance
			where id in (%s) and instance %s %s
			order by displayorder',
			$this->getItemList('integer'),
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'));

		$gadget_instances = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('SiteGadgetInstanceWrapper'));

		$titles = array();
		foreach ($gadget_instances as $gadget_instance) {
			$gadget = SiteGadgetFactory::get($this->app, $gadget_instance);
			$titles[] = $gadget->getTitle();
		}

		ob_start();

		$h3_tag = new SwatHtmlTag('h3');
		$h3_tag->setContent(Site::ngettext(
			'Remove the following gadget from the sidebar?',
			'Remove the following gadgets from the sidebar?',
			$this->getItemCount()));

		$h3_tag->display();

		echo '<ul>';

		foreach ($titles as $title) {
			$li_tag = new SwatHtmlTag('li');
			$li_tag->setContent($title);
			$li_tag->display();
		}

		echo '</ul>';

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = ob_get_clean();
		$message->content_type = 'text/xml';
	}

	// }}}
}

?>
