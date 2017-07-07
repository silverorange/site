<?php

/**
 * @package   Site
 * @copyright 2013-2016 silverorange
 */
class SiteAmazonCdnMediaManifestPage extends SitePage
{
	// {{{ protected properties

	protected $media = null;

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout($this->app, SiteSMILTemplate::class);
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

		$this->media->setFileBase('media');
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

		$amazon = $this->app->config->amazon;

		$port = $amazon->streaming_distribution_port;
		$distribution = $amazon->streaming_distribution;

		if ($this->media->media_set->private) {
			$port = $amazon->private_streaming_distribution_port;
			$distribution = $amazon->private_streaming_distribution;
		}

		if ($port !== null) {
			$distribution.= ':'.$port;
		}

		$meta_tag = new SwatHtmlTag('meta');
		$meta_tag->base = sprintf(
			'rtmp://%s/cfx/st/',
			$distribution
		);

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

			// Modern AWS SDK automatically chops off the base href for signed
			// RTMP URLs.
			$video_tag->src = $this->app->cdn->getStreamingUri(
				$this->media->getFilePath($encoding->width),
				($this->media->media_set->private) ? '1 day' : null
			);

			$video_tag->width = $binding->width;
			$video_tag->height = $binding->height;

			// system-bitrate is in kbps, so calculate it as:
			// file-size (bytes) / duration * 8 (to convert to bits)
			$system_bitrate = $this->media->getFileSize($encoding->shortname)
				/ $this->media->duration * 8;

			$video_tag->{'system-bitrate'} = (int)$system_bitrate;

			$tags[] = $video_tag->__toString();
		}

		echo implode("\n", $tags);

		echo '</switch>';
		echo '</body>';
		echo '</smil>';
	}

	// }}}
}

?>
