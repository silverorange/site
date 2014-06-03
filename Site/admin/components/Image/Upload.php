<?php

require_once 'Admin/pages/AdminObjectEdit.php';
require_once 'Site/dataobjects/SiteImage.php';

/**
 * Upload page for SiteImages
 *
 * @package   Site
 * @copyright 2014 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteImageUpload extends AdminObjectEdit
{
	// {{{ protected properties

	/**
	 * @var SiteImage
	 */
	protected $existing_image;

	/**
	 * The available dimensions of the SiteImage being uploaded.
	 *
	 * @var SiteImageDimensionWrapper
	 */
	protected $dimensions;

	/**
	 * An array of SwatFileEntryWidgets corroponding to each available
	 * dimension.
	 *
	 * @var array
	 */
	protected $dimension_upload_widgets;

	// }}}
	// {{{ abstract protected function getFileBase()

	abstract protected function getFileBase();

	// }}}
	// {{{ protected function allowDimensionUploads()

	protected function allowDimensionUploads()
	{
		return false;
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/admin/components/Image/upload.xml';
	}

	// }}}
	// {{{ protected function getDimensionUploadWidget()

	protected function getDimensionUploadWidget(SiteImageDimension $dimension)
	{
		if (!isset($this->dimension_upload_widgets[$dimension->shortname])) {
			throw SiteException(
				sprintf(
					'No upload widget for ‘%s’ dimension.',
					$dimension->shortname
				)
			);
		}

		return $this->dimension_upload_widgets[$dimension->shortname];
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->checkImageClass();
		$this->initUploadWidget();

		if ($this->allowDimensionUploads()) {
			$this->initDimensions();
			$this->initDimensionUploadWidgets();
		}
	}

	// }}}
	// {{{ protected function initObject()

	protected function initObject()
	{
		parent::initObject();

		// Replace an existing image to generate a new object and id to prevent
		// browser and CDN caching.
		if (!$this->isNew()) {
			$this->existing_image = $this->getObject();

			$class_name = $this->getResolvedObjectClass();
			$this->data_object = new $class_name();
			$this->data_object->setDatabase($this->app->db);

			if ($this->app->hasModule('SiteMemcacheModule')) {
				$this->data_object->setFlushableCache(
					$this->app->getModule('SiteMemcacheModule')
				);
			}
		}
	}

	// }}}
	// {{{ protected function checkImageClass()

	protected function checkImageClass()
	{
		if (!$this->getObject() instanceof SiteImage) {
			throw new AdminNotFoundException(
				'Image upload requires a SiteImage dataobject.'
			);
		}
	}

	// }}}
	// {{{ protected function initUploadWidget()

	protected function initUploadWidget()
	{
		$image = $this->getObject();

		$upload_widget = $this->ui->getWidget('upload_widget');
		$upload_widget->accept_mime_types = $image->getValidMimeTypes();
		$upload_widget->human_file_types  = $image->getValidHumanFileTypes();
		$upload_widget->required = (
			!$this->allowDimensionUploads() &&
			$this->isNew()
		);
	}

	// }}}
	// {{{ protected function initDimensions()

	protected function initDimensions()
	{
		if ($this->isNew()) {
			$class_name = SwatDBClassMap::get('SiteImageSet');
			$image_set = new $class_name();
			$image_set->setDatabase($this->app->db);
			$image_set->loadByShortname(
				$this->getObject()->getImageSetShortname()
			);

			$this->dimensions = $image_set->dimensions;
		} else {
			$this->dimensions = $this->getObject()->image_set->dimensions;
		}
	}

	// }}}
	// {{{ protected function initDimensions()

	protected function initDimensionUploadWidgets()
	{
		$image = $this->getObject();

		$dimensions_widget_container = $this->ui->getWidget(
			$this->getDimensionsWidgetContainerId()
		);

		$dimensions_widget_container->visible = true;

		foreach ($this->dimensions as $dimension) {
			$shortname = $dimension->shortname;
			$form_field = new SwatFormField(
				sprintf(
					'%s_field',
					$shortname
				)
			);

			$form_field->title = $dimension->title;

			$dimension_note = $this->getDimensionFieldNote($dimension);
			if ($dimension_note != '') {
				$form_field->note = $dimension_note;
			}

			$file_widget = new SwatFileEntry($shortname);
			$file_widget->display_mime_types = false;
			$file_widget->accept_mime_types  = $image->getValidMimeTypes();
			$file_widget->human_file_types   = $image->getValidHumanFileTypes();

			$form_field->addChild($file_widget);
			$dimensions_widget_container->addChild($form_field);

			$this->dimension_upload_widgets[$shortname] = $file_widget;
		}
	}

	// }}}
	// {{{ protected function getDimensionsWidgetContainerId()

	protected function getDimensionsWidgetContainerId()
	{
		return 'manual_fieldset';
	}

	// }}}
	// {{{ protected function getDimensionFieldNote()

	protected function getDimensionFieldNote(SiteImageDimension $dimension)
	{
		$dimension_note = '';

		if ($dimension->max_width !== null &&
			$dimension->max_height !== null) {
			$dimension_note = sprintf(
				Site::_('Maximum Dimensions: %s × %s px'),
				$dimension->max_width,
				$dimension->max_height
			);
		} elseif ($dimension->max_width !== null) {
			$dimension_note = sprintf(
				Site::_('Maximum Dimension: %s px wide'),
				$dimension->max_width
			);
		} elseif ($dimension->max_height !== null) {
			$dimension_note = sprintf(
				Site::_('Maximum Dimension: %s px high'),
				$dimension->max_width
			);
		}

		return $dimension_note;
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$valid = true;

		// If it's a new image, either the original image needs to be uploaded,
		// or all individual dimensions do.
		$automatic = $this->ui->getWidget('upload_widget');
		if ($this->isNew() &&
			!$automatic->isUploaded() &&
			!$this->validateAllDimensionsAreUploaded()) {
			$valid = false;

			$message = new SwatMessage(
				Site::_(
					'You must either upload a file to be automatically '.
					'resized or upload manually resized files for all '.
					'dimensions.'
				),
				'error'
			);

			$this->ui->getWidget('message')->add($message);
		}

		return $valid;
	}

	// }}}
	// {{{ protected function validateAllDimensionsAreUploaded()

	protected function validateAllDimensionsAreUploaded()
	{
		$valid = true;

		if ($this->allowDimensionUploads()) {
			foreach ($this->dimensions as $dimension) {
				$dimension_widget = $this->getDimensionUploadWidget($dimension);
				if (!$dimension_widget->isUploaded()) {
					$valid = false;
					break;
				}
			}
		}

		return $valid;
	}

	// }}}
	// {{{ protected function updateObject()

	protected function updateObject()
	{
		parent::updateObject();

		$upload_widget = $this->ui->getWidget('upload_widget');

		$image = $this->getObject();
		$image->setFileBase($this->getFileBase());

		if ($upload_widget->isUploaded()) {
			$image->original_filename = $upload_widget->getFileName();
			$image->process($upload_widget->getTempFileName());
		}

		if ($this->allowDimensionUploads()) {
			foreach ($this->dimensions as $dimension) {
				$dimension_widget = $this->getDimensionUploadWidget($dimension);
				if ($dimension_widget->isUploaded()) {
					$image->processManual(
						$dimension_widget->getTempFileName(),
						$dimension->shortname
					);
				}
			}
		}
	}

	// }}}
	// {{{ protected function postSaveObject()

	protected function postSaveObject()
	{
		parent::postSaveObject();

		if (!$this->isNew() &&
			$this->existing_image instanceof SiteImage) {
			$this->existing_image->setFileBase($this->getFileBase());
			$this->existing_image->delete();
		}
	}

	// }}}
	// {{{ protected function addSavedMessage()

	protected function addSavedMessage()
	{
		// Only show a saved message if some sort of file has been uploaded
		// (and hence processed).
		if ($this->fileIsUploaded()) {
			parent::addSavedMessage();
		}
	}

	// }}}
	// {{{ protected function getSavedMessagePrimaryContent()

	protected function getSavedMessagePrimaryContent()
	{
		return Site::_('Image has been saved.');
	}

	// }}}
	// {{{ protected function fileIsUploaded()

	protected function fileIsUploaded()
	{
		$file_uploaded = false;

		$file_uploaded = $this->ui->getWidget('upload_widget')->isUploaded();
		if (!$file_uploaded && $this->allowDimensionUploads()) {
			foreach ($this->dimensions as $dimension) {
				$dimension_widget = $this->getDimensionUploadWidget($dimension);
				if ($dimension_widget->isUploaded()) {
					$file_uploaded = true;
					break;
				}
			}
		}

		return $file_uploaded;
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

		$this->ui->getWidget('edit_frame')->title = $this->getTitle();
	}

	// }}}
	// {{{ protected function buildButton()

	protected function buildButton()
	{
		$this->ui->getWidget('submit_button')->title = Site::_('Upload');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntries(2);
		$this->navbar->createEntry($this->getTitle());
	}

	// }}}
	// {{{ protected function loadObject()

	protected function loadObject()
	{
		parent::loadObject();

		if (!$this->isNew() &&
			$this->existing_image instanceof SiteImage) {
			$image     = $this->existing_image;
			$dimension = $this->getDisplayedImageDimension();
			$path      = $this->getImagePath();

			$image_display = $this->ui->getWidget('image_display');
			$image_display->visible = true;
			$image_display->image   = $image->getUri($dimension, $path);
			$image_display->width   = $image->getWidth($dimension);
			$image_display->height  = $image->getHeight($dimension);
		}
	}

	// }}}
	// {{{ protected function getUploadStatusServer()

	protected function getUploadStatusServer()
	{
		return 'Image/UploadStatusServer';
	}

	// }}}
	// {{{ protected function getDisplayedImageDimension()

	protected function getDisplayedImageDimension()
	{
		return 'thumb';
	}

	// }}}
	// {{{ protected function getImagePath()

	protected function getImagePath()
	{
		return '../';
	}

	// }}}
	// {{{ protected function getTitle()

	protected function getTitle()
	{
		return ($this->isNew())
			? Site::_('Upload Image')
			: Site::_('Replace Image');
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/site/admin/styles/site-image-upload.css'
		);
	}

	// }}}
}

?>
