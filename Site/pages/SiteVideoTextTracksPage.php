<?php

require_once 'Site/dataobjects/SiteVideoMediaWrapper.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotAuthorizedException.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Swat/SwatHtmlTag.php';

/**
 * @package   Site
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteVideoTextTracksPage extends SitePage
{
	// {{{ protected properties

	protected $media = null;

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout($this->app, 'Site/layouts/xhtml/vtt.php');
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		if ($this->media === null) {
			throw new SiteNotFoundException('Media not specified');
		} elseif ($this->media->scrubber_image === null) {
			throw new SiteNotFoundException('Media doesn’t have a '.
				'scrubber image');
		}

		// for private videos, check if the user has access
		if ($this->media->private && (
			!$this->app->session->isActive() ||
			!isset($this->app->session->media_access) ||
			!$this->app->session->media_access->offsetExists
				($this->media->id))) {

			throw new SiteNotAuthorizedException('No access to private video.');
		}
	}

	// }}}
	// {{{ public function setMediaKey()

	public function setMediaKey($media_id)
	{
		$sql = sprintf('select * from Media where id = %s',
			$this->app->db->quote($media_id, 'integer'));

		$this->media = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('SiteVideoMediaWrapper'))->getFirst();

		if ($this->media === null) {
			throw new SiteNotFoundException('Media not found for id:'.
				$media_id);
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->layout->startCapture('content');
		$this->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function display()

	protected function display()
	{
		$interval = $this->media->getScrubberImageInterval();
		$uri = $this->media->scrubber_image->getUri('original',
			$this->app->getBaseHref());

		$image_width = $this->media->scrubber_image->getWidth('original');

		echo "WEBVTT\n\n";

		$seconds = 0;
		$image_offset = 0;
		while ($seconds < $this->media->duration) {
			$image_offset += $this->media->getScrubberImageWidth();

			echo $this->getFormattedTime($seconds);
			echo ' --> ';

			// Make the last frame stretch to the end - this is because
			// we offset frames forward so that you always see the frame in
			// the image when you seek. The last frame therefore has to be
			// longer.
			if ($image_offset + $this->media->getScrubberImageWidth()
				>= $image_width) {

				$seconds = $this->media->duration;
			} else {
				$seconds += $interval;
			}

			echo $this->getFormattedTime($seconds);
			echo "\n";
			printf('%s#xywh=%d,0,%d,%d',
				$uri,
				$image_offset,
				$this->media->getScrubberImageWidth(),
				$this->media->scrubber_image->getHeight('original'));

			echo "\n\n";
		}
	}

	// }}}
	// {{{ public function getFormattedTime()

	public function getFormattedTime($seconds)
	{
		// don't care about micro-seconds.
		$seconds = floor($seconds);

		$hours = 0;
		$minutes = 0;

		$minute = 60;
		$hour = $minute * 60;

		if ($seconds > $hour) {
			$hours = floor($seconds / $hour);
			$seconds -= $hour * $hours;
		}

		if ($seconds > $minute) {
			$minutes = floor($seconds / $minute);
			$seconds -= $minute * $minutes;
		}

		return sprintf('%02d:%02d:%02d,000',
			$hours, $minutes, $seconds);
	}

	// }}}
}

?>
