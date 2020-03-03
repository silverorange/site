<?php

/**
 * Upload page for SiteImages
 *
 * @package   Site
 * @copyright 2014-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteImageUpload extends AdminObjectEdit
{
	// {{{ protected properties

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
	// {{{ protected function shouldReplaceObject()

	protected function shouldReplaceObject()
	{
		// When editing/replacing an existing image, the uploaded file is not
		// required. This is to allow possible editing of secondary values, such
		// as individual dimensions if allowDimensionUploads() returns true, or
		// titles if the title widget is enabled. Only replace the existing
		// image with a new new object if a new file is uploaded. Otherwise
		// keep the old version. This does two things - it prevents image churn
		// on the site/CDN for images that art not actually modified, and it
		// fixes a bug when cloning a SiteImage where the main dataobject is
		// copied, but dimension bindings and files on disk are not, leaving
		// broken images.
		return ($this->ui->getWidget('upload_widget')->isUploaded());
	}

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
		return __DIR__.'/upload.xml';
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

		$this->initDimensions();
		if ($this->allowDimensionUploads()) {
			$this->initDimensionUploadWidgets();
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
	// {{{ protected function initDimensionUploadWidgets()

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

		// Need to update modified date here because SiteImage::process()
		// saves internally to get an image id for filenames. Normally, the
		// modified date is updated after the object is updated.
		$this->updateModifiedDate();

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
	// {{{ protected function deleteOldObject()

	protected function deleteOldObject()
	{
		if ($this->getOldObject() instanceof SiteImage) {
			$this->getOldObject()->setFileBase($this->getFileBase());
		}

		parent::deleteOldObject();
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
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildDimensionNotes();
	}

	// }}}
	// {{{ protected function buildDimensionNotes()

	protected function buildDimensionNotes()
	{
		$largest_width  = null;
		$largest_height = null;

		foreach ($this->dimensions as $dimension) {
			if ($dimension->max_width > $largest_width) {
				$largest_width = $dimension->max_width;
			}

			if ($dimension->max_height > $largest_height) {
				$largest_height = $dimension->max_height;
			}

			if ($this->allowDimensionUploads()) {
				$widget = $this->getDimensionUploadWidget($dimension);
				$dimension_note = $this->getDimensionFieldNote($dimension);
				if ($dimension_note != '') {
					$widget->parent->note = $dimension_note;
				}
			}
		}

		$minimum_dimension_note = '';
		if ($largest_width !== null &&
			$largest_height !== null) {
			$minimum_dimension_note = sprintf(
				Site::_('Minimum Suggested Dimensions: %s × %s px'),
				$largest_width,
				$largest_height
			);
		} elseif ($largest_width !== null) {
			$minimum_dimension_note = sprintf(
				Site::_('Minimum Suggested Dimension: %s px wide'),
				$largest_width
			);
		} elseif ($largest_height !== null) {
			$minimum_dimension_note = sprintf(
				Site::_('Minimum Suggested Dimension: %s px high'),
				$largest_height
			);
		}

		if ($minimum_dimension_note != '') {
			$this->ui->getWidget('upload_widget')->parent->note =
				$minimum_dimension_note;
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

		if (!$this->isNew()) {
			$image     = $this->getObject();
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
				$dimension->max_height
			);
		}

		return $dimension_note;
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
