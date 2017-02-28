<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Site/dataobjects/SiteVideoMedia.php';

/**
 * Poster frame edit page for SiteVideoMedia
 *
 * @package   Site
 * @copyright 2017 silverorange
 */
class SiteMediaPosterFrame extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var SiteVideoMedia
	 */
	protected $media;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->getUiXml());
		$this->initMedia();
	}

	// }}}
	// {{{ protected function initMedia()

	protected function initMedia()
	{
		$class_name = SwatDBClassMap::get('SiteVideoMedia');
		$this->media = new $class_name();
		$this->media->setDatabase($this->app->db);

		if ($this->id == '') {
			throw new AdminNotFoundException('A media id is required.');
		}

		if (!$this->media->load($this->id)) {
			throw new AdminNotFoundException(
				sprintf(
					'A media row with the id of ‘%s’ does not exist',
					$this->id
				)
			);
		}

		$instance_id = $this->app->getInstanceId();
		if ($instance_id != '') {
			if ($this->getMediaInstanceId() !== $instance_id) {
				throw new AdminNotFoundException(
					sprintf(
						'Incorrect instance for media ‘%s’.', $this->id
					)
				);
			}
		}
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/poster-frame.xml';
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$image_entry = $this->ui->getWidget('custom_image');
		if ($image_entry->isUploaded()) {
			$class_name = SwatDBClassMap::get('SiteVideoImage');
			$image = new $class_name();

			if ($image->hasProperty('modified_date')) {
				$image->modified_date = new SwatDate();
				$image->modified_date->toUTC();
			}

			$image->setDatabase($this->app->db);
			$image->setFileBase('../images');
			$image->process($image_entry->getTempFileName());

			// Delete the old image. Prevents broswer/CDN caching.
			if ($this->media->image instanceof SiteImage) {
				$this->media->image->setFileBase('../images');
				$this->media->image->delete();
			}

			$this->media->image = $image;
			$this->app->messages->add(new SwatMessage('Poster frame updated'));
			$this->media->save();
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->media->setFileBase('media');
		$player = $this->media->getMediaPlayer($this->app);
		ob_start();
		$player->display();
		$this->ui->getWidget('player')->content = ob_get_clean();
		$this->layout->addHtmlHeadEntrySet($player->getHtmlHeadEntrySet());
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues($this->media->getAttributes());
	}

	// }}}
	// {{{ protected function getMediaInstance()

	protected function getMediaInstance()
	{
		return $this->media->media_set->instance;
	}

	// }}}
	// {{{ protected function getMediaInstanceId()

	protected function getMediaInstanceId()
	{
		return ($this->getMediaInstance() instanceof SiteInstance)
			? $this->getMediaInstance()->id
			: null;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntries(2);

		if ($this->app->isMultipleInstanceAdmin()) {
			$instance = $this->getMediaInstance();
			$instance_link = sprintf('Instance/Details?id=%s', $instance->id);
			$this->layout->navbar->createEntry(
				$instance->title,
				$instance_link
			);
		}

		$this->navbar->createEntry(Site::_('Edit Poster Frame'));
	}

	// }}}
}
?>
