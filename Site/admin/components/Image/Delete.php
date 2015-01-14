<?php

require_once 'Admin/pages/AdminObjectDelete.php';
require_once 'Site/dataobjects/SiteImageWrapper.php';

/**
 * Abstract delete page for site images.
 *
 * @package   Site
 * @copyright 2014-2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteImageDelete extends AdminObjectDelete
{
	// {{{ abstract protected function getFileBase()

	abstract protected function getFileBase();

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/admin/components/Image/delete.xml';
	}

	// }}}

	// init phase
	// {{{ protected function getObjectsSql()

	protected function getObjectsSql()
	{
		return sprintf(
			'select * from Image where id in (%s)',
			$this->getItemList('integer')
		);
	}

	// }}}

	// process phase
	// {{{ protected function deleteObject()

	protected function deleteObject(SwatDBDataObject $object)
	{
		$object->setFileBase($this->getFileBase());
		$object->delete();
	}

	// }}}
	// {{{ protected function getSavedMessagePrimaryContent()

	protected function getSavedMessagePrimaryContent($delete_count)
	{
		return sprintf(
			Site::ngettext(
				'One image has been deleted.',
				'%s images have been deleted.',
				$delete_count
			),
			$locale->formatNumber($delete_count)
		);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildDeleteView();
		$this->buildButtons();
	}

	// }}}
	// {{{ protected function buildDeleteView()

	protected function buildDeleteView()
	{
		$delete_view = $this->ui->getWidget('delete_view');
		$delete_view->model = $this->getImageStore();
	}

	// }}}
	// {{{ protected function buildButtons()

	protected function buildButtons()
	{
		$this->ui->getWidget('yes_button')->title = $this->getTitle();
	}

	// }}}
	// {{{ protected function buildNavbar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		// Clear the delete navbar entry added by parent classes.
		$this->navbar->popEntry();
		$this->navbar->createEntry($this->getTitle());
	}

	// }}}
	// {{{ protected function getConfirmationMessageHeader()

	protected function getConfirmationMessageHeader()
	{
		return Site::ngettext(
			'Delete the following image?',
			'Delete the following images?',
			count($this->getObjects())
		);
	}

	// }}}
	// {{{ protected function getTitle()

	protected function getTitle()
	{
		return Site::ngettext(
			'Delete Image',
			'Delete Images',
			count($this->getObjects())
		);
	}

	// }}}
	// {{{ protected function getImageStore()

	protected function getImageStore()
	{
		$store = new SwatTableStore();

		foreach ($this->getObjects() as $object) {
			$ds = new SwatDetailsStore();
			$ds->image = $object;

			$store->add($ds);
		}

		return $store;
	}

	// }}}
}

?>
