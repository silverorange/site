<?php

require_once 'Admin/pages/AdminObjectEdit.php';

/**
 * Upload page for attachments
 *
 * @package   Site
 * @copyright 2014-2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteAttachmentUpload extends AdminObjectEdit
{
	// {{{ abstract protected function getFileBase()

	abstract protected function getFileBase();

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/admin/components/Attachment/upload.xml';
	}

	// }}}
	// {{{ protected function getObjectUiValueNames()

	protected function getObjectUiValueNames()
	{
		return array('title');
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->checkAttachmentClass();
		$this->initUploadWidget();
	}

	// }}}
	// {{{ protected function checkAttachmentClass()

	protected function checkAttachmentClass()
	{
		if (!$this->getObject() instanceof SiteAttachment) {
			throw new AdminNotFoundException(
				'Attachment upload requires a SiteAttachment dataobject.'
			);
		}
	}

	// }}}
	// {{{ protected function initUploadWidget()

	protected function initUploadWidget()
	{
		$upload_widget = $this->ui->getWidget('upload_widget');
		$upload_widget->accept_mime_types =
			$this->getObject()->getValidMimeTypes();

		$upload_widget->human_file_types =
			$this->getObject()->getValidHumanFileTypes();

	}

	// }}}

	// process phase
	// {{{ protected function updateObject()

	protected function updateObject()
	{
		parent::updateObject();

		$upload_widget = $this->ui->getWidget('upload_widget');

		$attachment = $this->getObject();

		$attachment->file_size         = $upload_widget->getSize();
		$attachment->mime_type         = $upload_widget->getMimeType();
		$attachment->original_filename = $upload_widget->getFileName();
		$attachment->human_filename    = $this->getHumanFileName();

		$attachment->setFileBase($this->getFileBase());
		$attachment->process($upload_widget->getTempFileName());
	}

	// }}}
	// {{{ protected function getSavedMessagePrimaryContent()

	protected function getSavedMessagePrimaryContent()
	{
		$attachment = $this->getObject();
		if ($attachment->human_filename != '') {
			$content = sprintf(
				Site::_('“%s” has been uploaded as “%s”.'),
				$attachment->original_filename,
				$attachment->human_filename
			);
		} else {
			$content = sprintf(
				Site::_('“%s” has been uploaded.'),
				$attachment->original_filename
			);
		}

		return $content;
	}

	// }}}
	// {{{ protected function getHumanFilename()

	protected function getHumanFilename()
	{
		return '';
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$this->app->addToSearchQueue('attachment', $this->getObject()->id);
	}

	// }}}

	// build phase
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();

		$form = $this->ui->getWidget('edit_form');
		if ($form instanceof SiteUploadProgressForm) {
			$form->upload_status_server = $this->getUploadStatusServer();
		}
	}

	// }}}
	// {{{ protected function buildFrame()

	protected function buildFrame()
	{
		parent::buildFrame();

		$this->ui->getWidget('edit_frame')->title =
			Site::_('Upload Attachment');
	}

	// }}}
	// {{{ protected function buildButton()

	protected function buildButton()
	{
		$this->ui->getWidget('submit_button')->title =
			Site::_('Upload Attachment');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntries(2);
		$this->navbar->createEntry(Site::_('Upload Attachment'));
	}

	// }}}
	// {{{ protected function getUploadStatusServer()

	protected function getUploadStatusServer()
	{
		return 'Attachment/UploadStatusServer';
	}

	// }}}
}

?>
