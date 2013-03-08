<?php

require_once 'AWSSDKforPHP/sdk.class.php';
require_once 'Site/dataobjects/SiteVideoMediaWrapper.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotAuthorizedException.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Swat/SwatHtmlTag.php';

/**
 * @package   Site
 * @copyright 2013 silverorange
 */
class SiteAmazonCdnMediaManifestPage extends SitePage
{
	// {{{ protected properties

	protected $media = null;

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout($this->app, 'Site/layouts/xhtml/smil.php');
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		if ($this->media === null) {
			throw new SiteNotFoundException('Media not specified');
		}

		// for private videos, check if the user has access
		if ($this->media->media_set->private && (
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
		echo '<smil>';
		echo '<head>';

		$distribution = $this->app->config->amazon->streaming_distribution;

		$meta_tag = new SwatHtmlTag('meta');
		$meta_tag->base = sprintf('rtmp://%s/cfx/st/', $distribution);
		$meta_tag->display();

		echo '</head>';
		echo '<body>';
		echo '<switch>';

		// TODO: memcache everything but content-signing
		$tags = array();
		foreach ($this->media->media_set->encodings as $encoding) {
			$binding = $this->media->getEncodingBinding($encoding->shortname);
			if ($binding === null || !$binding->on_cdn || $encoding->width <= 0) {
				continue;
			}

			$video_tag = new SwatHtmlTag('video');

			$file_path = $this->media->getFilePath($encoding->width);
			$path = $this->app->cdn->getStreamingUri($file_path,
				($this->media->media_set->private) ? '1 day' : '1 year');

			$parts = parse_url($path);
			$video_tag->src = substr($parts['path'], 1).'?'.$parts['query'];
			$video_tag->width = $binding->width;
			$video_tag->height = $binding->height;

			// system-bitrate is in kbps, so calculate it as:
			// file-size (bytes) / duration * 8 (to convert to bits)
			$system_bitrate = $this->media->getFileSize($encoding->shortname)
				/ $this->media->duration * 8;
			
			$video_tag->{'system-bitrate'} = (int)$system_bitrate;

			$tags[] = $video_tag->__toString();
		}

		echo implode("\n", array_reverse($tags));

		echo '</switch>';
		echo '</body>';
		echo '</smil>';
	}

	// }}}
}

?>
