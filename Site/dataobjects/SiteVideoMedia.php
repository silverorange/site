<?php

require_once 'Site/dataobjects/SiteMedia.php';
require_once 'Site/dataobjects/SiteVideoImage.php';
require_once 'Site/dataobjects/SiteVideoMediaSet.php';
require_once 'Site/dataobjects/SiteVideoMediaEncodingBindingWrapper.php';

/**
 * A video-specific media object
 *
 * @package   Site
 * @copyright 2011-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteVideoMedia extends SiteMedia
{
	// {{{ public properties

	/**
	 * BOTR key
	 *
	 * @var string
	 */
	public $key;

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

	public function getMediaPlayer(SiteApplication $app,
		$player_config = array())
	{
		$jwplayer = new SiteJwPlayerMediaDisplay('video'.$this->id);
		$jwplayer->setMedia($this);
		$jwplayer->key = $app->config->jwplayer->key;
		
		if ($app->session->isActive()) {
			$jwplayer->setSession($app->session);
		}	

		$expires = ($this->media_set->private) ? '1 day' : '1 year';

		foreach ($this->encoding_bindings as $binding) {
			if ($binding->on_cdn && $binding->width > 0) {
				$jwplayer->addSource(
					$app->cdn->getUri($this->getFilePath($binding->width), $expires),
					$binding->width.'p');
			}
		}

		$config = array_merge($this->getDefaultMediaPlayerConfig(),
			$player_config);

		if ($this->image !== null) {
			$jwplayer->image = $this->image->getUri($config['image_dimension']);
		}

		$jwplayer->start_position = $config['start_position'];
		$jwplayer->record_end_point = $config['record_end_point'];
		$jwplayer->on_complete_message = $config['on_complete_message'];

		return $jwplayer;
	}

	// }}}
	// {{{ public function getFilePath()

	/**
	 * Gets the full file path of an encoding
	 *
	 * This includes the directory and the filename.
	 *
	 * @return string the full file path of an encoding
	 */
	public function getFilePath($width)
	{
		$path = sprintf('media/%s/%s/%s.mp4',
			$this->media_set->shortname,
			$width,
			$this->id);

		return $path;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('image',
			SwatDBClassMap::get('SiteVideoImage'));

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
	// {{{ protected function getDefaultMediaPlayerConfig()

	protected function getDefaultMediaPlayerConfig()
	{
		return array(
			'image_dimension' => '720',
			'start_position' => 0,
			'record_end_point' => false,
			'on_complete_message' => null,
		);
	}

	// }}}
}

?>
