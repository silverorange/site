<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';

/**
 * Delete confirmation page for Ads
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAdDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = $this->getDeleteSql();
		$num = SwatDB::exec($this->app->db, $sql);
		$message = new SwatMessage(sprintf(Site::ngettext(
			'One ad has been deleted.', '%s ads have been deleted.', $num),
			SwatString::numberFormat($num)), 'notice');

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function getDeleteSql()

	protected function getDeleteSql()
	{
		$item_list = $this->getItemList('integer');
		return sprintf('delete from Ad where id in (%s)',
			$item_list);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$dep = $this->getDependencies();

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
	// {{{ protected function getDependencies()

	protected function getDependencies()
	{
		$item_list = $this->getItemList('integer');

		$dep = new AdminListDependency();
		$dep->setTitle(Site::_('ad'), Site::_('ads'));
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'Ad', 'integer:id', null, 'text:title', 'id',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		return $dep;
	}

	// }}}
}

?>
