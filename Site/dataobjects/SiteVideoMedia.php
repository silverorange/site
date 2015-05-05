<?php

require_once 'Site/SiteJwPlayerMediaDisplay.php';
require_once 'Site/dataobjects/SiteMedia.php';
require_once 'Site/dataobjects/SiteVideoImage.php';
require_once 'Site/dataobjects/SiteVideoScrubberImage.php';
require_once 'Site/dataobjects/SiteVideoMediaSet.php';
require_once 'Site/dataobjects/SiteVideoMediaEncodingBindingWrapper.php';

/**
 * A video-specific media object
 *
 * @package   Site
 * @copyright 2011-2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteVideoMedia extends SiteMedia
{
	// {{{ public properties

	/**
	 * Unique string key
	 *
	 * @var string
	 */
	public $key;

	/**
	 * Scrubber image count
	 *
	 * @var integer
	 */
	public $scrubber_image_count;

	/**
	 * Has HLS encodings
	 *
	 * @var boolean
	 */
	public $has_hls;

	// }}}
	// {{{ public function getHumanFileType()

	public function getHumanFileType($encoding_shortname = null)
	{
		if ($encoding_shortname === null) {
			$binding = $this->getLargestVideoEncodingBinding();

			if ($binding === null) {
				throw new SiteException(sprintf(
					'Encoding “%s” does not exist for media “%s”.',
						$encoding_shortname, $this->id));
			}

			$file_type = $binding->getHumanFileType();
		} else {
			$file_type = parent::getHumanFileType($encoding_shortname);
		}

		return $file_type;
	}

	// }}}
	// {{{ public function getFormattedFileSize()

	public function getFormattedFileSize($encoding_shortname = null)
	{
		if ($encoding_shortname === null) {
			$binding = $this->getLargestVideoEncodingBinding();

			if ($binding === null) {
				throw new SiteException(sprintf(
					'Encoding “%s” does not exist for media “%s”.',
						$encoding_shortname, $this->id));
			}

			$file_size = $binding->getFormattedFileSize();
		} else {
			$file_size = parent::getFormattedFileSize($encoding_shortname);
		}

		return $file_size;
	}

	// }}}
	// {{{ public function getLargestVideoEncodingBinding()

	public function getLargestVideoEncodingBinding()
	{
		$largest = null;

		foreach ($this->encoding_bindings as $binding) {
			if ($largest === null) {
				$largest = $binding;
			}

			if ($binding->width > $largest->width) {
				$largest = $binding;
			}
		}

		return $largest;
	}

	// }}}
	// {{{ public function getSmallestVideoEncodingBinding()

	public function getSmallestVideoEncodingBinding()
	{
		$smallest = null;

		foreach ($this->encoding_bindings as $binding) {
			if ((($smallest === null) && ($binding->width !== null)) ||
				(($smallest !== null) &&
					($binding->width < $smallest->width))) {
				$smallest = $binding;
			}
		}

		return $smallest;
	}

	// }}}
	// {{{ public function getDefaultAudioEncoding()

	public function getDefaultAudioEncoding()
	{
		$audio = null;

		foreach ($this->encoding_bindings as $binding) {
			// Return first encoding that has an audio mime type. This can be
			// improved in the future.
			if (strpos($binding->media_type->mime_type, 'audio') !== false) {
				$audio = $binding;
				break;
			}
		}

		return $audio;
	}

	// }}}
	// {{{ public function getMediaPlayer()

	public function getMediaPlayer(SiteApplication $app)
	{
		$jwplayer = new SiteJwPlayerMediaDisplay('video'.$this->id);
		$jwplayer->setMedia($this);
		$jwplayer->swf_uri = 'packages/site/javascript/jwplayer.flash.swf';
		$jwplayer->key = $app->config->jwplayer->key;

		$jwplayer->menu_title = $app->config->site->title;
		$jwplayer->menu_link = $app->getBaseHref();

		// Android 2 can not play back video over HTTPS from CloudFront, so
		// force the sources as HTTP.
		$secure = $app->isSecure();
		if ($app->hasModule('SiteMobileModule') &&
			$app->mobile->isAndroid() &&
			$app->mobile->getPlatformMajorVersion() !== null &&
			$app->mobile->getPlatformMajorVersion() == 2) {
			$secure = false;
		}

		if ($app->session->isActive()) {
			$jwplayer->setSession($app->session);
		}

		$expires = ($this->media_set->private) ? '1 day' : null;

		if ($this->has_hls) {
			$jwplayer->addSource(
				$app->cdn->getUri(
					$this->getHlsFilePath(),
					$expires,
					$secure
				)
			);
		}

		foreach ($this->media_set->encodings as $encoding) {
			if (!$this->encodingExists($encoding->shortname)) {
				continue;
			}

			$binding = $this->getEncodingBinding($encoding->shortname);
			if ($binding->on_cdn && $binding->width > 0) {
				$jwplayer->addSource(
					$app->cdn->getUri(
						$this->getFilePath($encoding->shortname),
						$expires,
						$secure
					),
					$binding->width,
					$binding->height.'p');
			}
		}

		if ($this->image !== null) {
			$dimensions = $this->image->image_set->dimensions;
			foreach ($dimensions as $dimension) {
				if ($this->image->hasDimension($dimension->shortname)) {
					$jwplayer->addImage(
						$this->image->getUri($dimension->shortname),
						$this->image->getWidth($dimension->shortname));
				}
			}
		}

		return $jwplayer;
	}

	// }}}
	// {{{ public function getMediaPlayerByKey()

	public function getMediaPlayerByKey(SiteApplication $app,
		$key, $file_base = 'media')
	{
		if ($this->db === null) {
			$this->setDatabase($app->db);
		}

		if (!$this->loadByKey($key)) {
			throw new SwatException('Video not found for key: '.$key);
		}

		$this->setFileBase($file_base);
		return $this->getMediaPlayer($app);
	}

	// }}}
	// {{{ public function getMimeTypes()

	public function getMimeTypes()
	{
		$types = array();
		foreach ($this->encoding_bindings as $binding) {
			if ($binding->width !== null && $binding->width > 0) {
				$mime_type = $binding->media_type->mime_type;
				$types[$mime_type] = $mime_type;
			}
		}

		return $types;
	}

	// }}}
	// {{{ public function getScrubberImageInterval()

	public function getScrubberImageInterval()
	{
		$count = ($this->scrubber_image_count > 0) ?
			$this->scrubber_image_count : $this->getDefaultScrubberImageCount();

		return ($this->duration / $count);
	}

	// }}}
	// {{{ public function getDefaultScrubberImageCount()

	public function getDefaultScrubberImageCount()
	{
		// only used for generating scrubber images. For displaying them,
		// use SiteVideoMedia::$scrubber_image_count
		return 100;
	}

	// }}}
	// {{{ public function getScrubberImageWidth()

	public function getScrubberImageWidth()
	{
		return 130;
	}

	// }}}
	// {{{ public function getFileDirectory()

	public function getFileDirectory($encoding_shortname)
	{
		$directory = parent::getFileDirectory($encoding_shortname);

		if ($this->has_hls) {
			$directory = implode(
				DIRECTORY_SEPARATOR,
				array(
					$this->getFileBase(),
					$this->uuid,
					'full'
				)
			);
		}

		return $directory;

	}

	// }}}
	// {{{ public function getFilename()

	public function getFilename($encoding_shortname)
	{
		$binding = $this->getEncodingBinding($encoding_shortname);

		if ($this->getMediaSet()->obfuscate_filename) {
			$filename = $this->filename;
		} elseif ($this->has_hls) {
			$filename = $encoding_shortname;
		} else {
			$filename = $this->id;
		}

		return sprintf('%s.%s',
			$filename,
			$binding->media_type->extension
		);
	}

	// }}}
	// {{{ public function getHlsFilePath()

	public function getHlsFilePath()
	{
		$items = array(
			$this->getFileBase(),
			$this->uuid,
			'hls',
			'index.m3u8',
		);

		return implode(DIRECTORY_SEPARATOR, $items);
	}

	// }}}
	// {{{ public function getUriSuffix()

	public function getUriSuffix($encoding_shortname)
	{
		$suffix = parent::getUriSuffix($encoding_shortname);

		if ($this->has_hls) {
			$suffix = sprintf(
				'%s/%s/%s',
				$this->uuid,
				'full',
				$this->getFilename($encoding_shortname)
			);

			if ($this->getUriBase() != '') {
				$suffix = $this->getUriBase().'/'.$suffix;
			}
		}

		return $suffix;
	}

	// }}}
	// {{{ public function loadByKey()

	/**
	 * Loads an video from its key
	 *
	 * @param string $key the key of the video to load.
	 *
	 * @return boolean true if the loading of this video was successful and
	 *                  false if the video with the given key doesn't
	 *                  exist.
	 */
	public function loadByKey($key)
	{
		$this->checkDB();

		$row = null;

		if ($this->table !== null) {
			$sql = sprintf('select * from %s where key = %s',
				$this->table,
				$this->db->quote($key));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row === null)
			return false;

		$this->initFromRow($row);
		$this->generatePropertyHashes();

		return true;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('image',
			SwatDBClassMap::get('SiteVideoImage'));

		$this->registerInternalProperty('scrubber_image',
			SwatDBClassMap::get('SiteVideoScrubberImage'));

		$this->registerInternalProperty('media_set',
			SwatDBClassMap::get('SiteVideoMediaSet'));
	}

	// }}}
	// {{{ protected function getMediaEncodingBindingWrapperClass()

	protected function getMediaEncodingBindingWrapperClass()
	{
		return SwatDBClassMap::get('SiteVideoMediaEncodingBindingWrapper');
	}

	// }}}
	// {{{ protected function getMediaEncodingBindingsOrderBy()

	protected function getMediaEncodingBindingsOrderBy()
	{
		// Load encodings by size, but put nulls first since those would be
		// audio only encodings.
		return 'order by width asc nulls first';
	}

	// }}}
	// {{{ protected function loadVideoEncodingBindings()

	protected function loadVideoEncodingBindings()
	{
		$sql = sprintf(
			'select * from MediaEncodingBinding
			where MediaEncodingBinding.media = %s and height > %s',
			$this->db->quote($this->id, 'integer'),
			$this->db->quote(0, 'integer')
		);

		return SwatDB::query(
			$this->db,
			$sql,
			$this->getMediaEncodingBindingWrapperClass()
		);
	}

	// }}}
}

?>
