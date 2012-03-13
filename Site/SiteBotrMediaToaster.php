<?php

require_once 'BotrAPI.php';
require_once 'Swat/SwatHtmlHeadEntrySet.php';
require_once 'Site/exceptions/SiteBotrMediaToasterException.php';
require_once 'Site/dataobjects/SiteBotrMedia.php';
require_once 'Site/dataobjects/SiteBotrMediaEncoding.php';
require_once 'Site/dataobjects/SiteBotrMediaPlayer.php';

/**
 * Wrapper class for the Bits on the Run API.
 *
 * Amiga computers were amazing.
 *
 * @package   Site
 * @copyright 2011-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaToaster
{
	// {{{ class constants

	/**
	 * On operations that can be paged, number of records to return with each
	 * call.
	 *
	 * Must be kept low enough to not timeout. Default if 50, and maximum is
	 * 1000.
	 *
	 * @var integer
	 */
	const CHUNK_SIZE = 50;

	/**
	 * Error code returned when attempting to do operations on media that
	 * doesn't exist.
	 */
	const NOT_FOUND_ERROR_CODE = 'NotFound';

	// }}}
	// {{{ protected properties

	/*
	 * var BotrAPI
	 */
	protected $backend;

	/**
	 * Whether or not to enable content signing when getting and displaying
	 * content. Defaults to false.
	 *
	 * @var boolean
	 */
	protected $content_signing = false;

	/**
	 * If {@link SiteBotrMediaToaster::$content_signing} is true, whether or not
	 * to treat the content as public or private.
	 *
	 * If true, the content is considered public and uses
	 * {@link SiteBotrMediaToaster::$public_content_expiry}, if false, the
	 * content is considered private and uses
	 * {@link MediaToaster::$private_content_expiry} for the content expiry.
	 * Defaults to false as most content is private.
	 *
	 * @var boolean.
	 */
	protected $content_public = false;

	/**
	 * If {@link SiteBotrMediaToaster::$content_signing} is true, and
	 * {@link SiteBotrMediaToaster::$content_public} is false how far into the
	 * future to set the content expiry.
	 *
	 * This should be a string that strtotime() can handle. Defaults to
	 * '+1 Minute'. Expiry for private content should be as short as possible to
	 * prevent sharing embedding and downloading links.
	 *
	 * @var string
	 */
	protected $private_content_expiry = '+1 Minute';

	/**
	 * If {@link SiteBotrMediaToaster::$content_signing} is true, and
	 * {@link SiteBotrMediaToaster::$content_public} is true how far into the
	 * future to set the content expiry.
	 *
	 * This should be a string that strtotime() can handle. Defaults to
	 * '+1 Year'. Public content expiry can safely be set to a far future expiry
	 * date.
	 *
	 * @var string
	 */
	protected $public_content_expiry = '+1 Year';

	// }}}

	// {{{ public function __construct()

	public function __construct(SiteApplication $app) {
		$this->app = $app;

		$this->setContentSigning($app->config->botr->content_signing);
		$this->setPrivateContentExpiry(
			$app->config->botr->private_content_expiry);

		$this->setPublicContentExpiry(
			$app->config->botr->public_content_expiry);

		$this->html_head_entry_set = new SwatHtmlHeadEntrySet();
	}

	// }}}
	// {{{ public function setContentSigning()

	public function setContentSigning($content_signing = true)
	{
		$this->content_signing = (bool)$content_signing;
	}

	// }}}
	// {{{ public function setContentPublic()

	public function setContentPublic($content_public = true)
	{
		$this->content_public = (bool)$content_public;
	}

	// }}}
	// {{{ public function setPrivateContentExpiry()

	public function setPrivateContentExpiry($private_content_expiry)
	{
		$this->private_content_expiry = $private_content_expiry;
	}

	// }}}
	// {{{ public function setPublicContentExpiry()

	public function setPublicContentExpiry($public_content_expiry)
	{
		$this->public_content_expiry = $public_content_expiry;
	}

	// }}}

	// uploading methods
	// {{{ public function getNewMediaUploadUrl()

	public function getNewMediaUploadUrl($redirect_address = null)
	{
		$response = $this->callBackend('/videos/create');

		return $this->getUploadUrl($response, $redirect_address);
	}

	// }}}
	// {{{ protected function getUploadUrl()

	/**
	 * Generates an upload URL
	 *
	 * This method can be used create any of the new content types Botr support,
	 * aka videos, players, watermarks, etc. See botr documentation here:
	 * {@link http://developer.longtailvideo.com/botr/system-api/uploads.html}
	 * for further details on how this url is built.
	 *
	 * @param array $response the response from BotrAPI create call
	 * @param string $redirect_address the address to redirect to on successful
	 *                                 upload.
	 *
	 * @return string the upload url to post the form to.
	 */
	public function getUploadUrl(array $response,
		$redirect_address = null)
	{
		// TODO: what should we use for api_format? BotrAPI uses php, so using
		// for now.
		$link = sprintf(
			'%s://%s%s?api_format=php&key=%s&token=%s',
			$response['link']['protocol'],
			$response['link']['address'],
			$response['link']['path'],
			$response['link']['query']['key'],
			$response['link']['query']['token']);

		if ($redirect_address != null) {
			$link.= '&redirect_address='.$redirect_address;
		}

		return $link;
	}

	// }}}

	// Content Management and API Methods
	// {{{ public function listMedia()

	public function listMedia(array $options = array())
	{
		$media    = array();
		$settings = array(
			'result_limit'  => self::CHUNK_SIZE,
			'result_offset' => 0,
			);

		$valid_options = array(
			'tags',
			'tags_mode',
			'text',
			'author',
			'mediatypes_filter',
			'statuses_filter',
			'order_by',
			'start_date',
			'end_date',
			'result_limit',
			'result_offset',
			);

		foreach ($options as $key => $value) {
			if (array_search($key, $valid_options) !== false) {
				$settings[$key] = $value;
			} else {
				throw new SiteBotrMediaToasterException(
					sprintf('listMedia() does not support %s option', $key));
			}
		}

		$chunk = $this->callBackend('/videos/list', $settings);

		while (count($chunk['videos']) > 0) {
			$media = array_merge($media, $chunk['videos']);
			$settings['result_offset'] += $settings['result_limit'];

			$chunk = $this->callBackend('/videos/list', $settings);
		}

		return $media;
	}

	// }}}
	// {{{ public function getMedia()

	public function getMedia(SiteBotrMedia $media)
	{
		return $this->getMediaByKey($media->key);
	}

	// }}}
	// {{{ public function getMediaByKey()

	public function getMediaByKey($key)
	{
		$options = array(
			'video_key' => $key,
			);

		$response = $this->callBackend('/videos/show', $options);

		return $response['video'];
	}

	// }}}
	// {{{ public function deleteMedia()

	public function deleteMedia(SiteBotrMedia $media)
	{
		return $this->deleteMediaByKey($media->key);
	}

	// }}}
	// {{{ public function deleteMediaByKey()

	public function deleteMediaByKey($key)
	{
		$options = array(
			'video_key' => $key,
			);

		$response = $this->callBackend('/videos/delete', $options);

		return ($response['status'] == 'ok');
	}

	// }}}
	// {{{ public function encodeMedia()

	public function encodeMedia(SiteBotrMedia $media,
		SiteBotrMediaEncoding $encoding)
	{
		return $this->encodeMediaByKeys($media->key, $encoding->key);
	}

	// }}}
	// {{{ public function encodeMediaByKeys()

	public function encodeMediaByKeys($media_key, $encoding_key)
	{
		$options = array(
			'video_key'    => $media_key,
			'template_key' => $encoding_key,
			);

		$response = $this->callBackend('/videos/conversions/create', $options);

		return $response['conversion']['key'];
	}

	// }}}
	// {{{ public function deleteEncoding()

	public function deleteEncoding(SiteBotrMediaEncodingBinding $encoding)
	{
		return $this->deleteEncodingByKey($encoding->key);
	}

	// }}}
	// {{{ public function deleteEncodingByKey()

	public function deleteEncodingByKey($key)
	{
		$options = array(
			'conversion_key' => $key,
			);

		$response = $this->callBackend('/videos/conversions/delete', $options);

		return ($response['status'] == 'ok');
	}

	// }}}
	// {{{ public function getEncodings()

	public function getEncodings(SiteBotrMedia $media)
	{
		return $this->getEncodingsByKey($media->key);
	}

	// }}}
	// {{{ public function getEncodingsByKey()

	public function getEncodingsByKey($key)
	{
		$options = array(
			'video_key' => $key,
			);

		$response = $this->callBackend('/videos/conversions/list', $options);

		return $response['conversions'];
	}

	// }}}
	// {{{ public function getOriginal()

	public function getOriginal(SiteBotrMedia $media)
	{
		return $this->getOriginalByKey($media->key);
	}

	// }}}
	// {{{ public function getOriginalByKey()

	public function getOriginalByKey($key)
	{
		$original  = false;
		$encodings = $this->getEncodingsByKey($key);

		foreach ($encodings as $encoding) {
			if ($encoding['template']['format']['key'] == 'original') {
				$original = $encoding;
				break;
			}
		}

		return $original;
	}

	// }}}
	// {{{ public function getPassthrough()

	public function getPassthrough(SiteBotrMedia $media)
	{
		return $this->getPassthroughByKey($media->key);
	}

	// }}}
	// {{{ public function getPassthroughByKey()

	public function getPassthroughByKey($key)
	{
		$passthrough = false;
		$encodings   = $this->getEncodingsByKey($key);

		foreach ($encodings as $encoding) {
			if ($encoding['template']['format']['key'] == 'passthrough') {
				$passthrough = $encoding;
				break;
			}
		}

		return $passthrough;
	}

	// }}}
	// {{{ public function getEncodingByWidth()

	public function getEncodingByWidth($key, $width)
	{
		$encoding  = false;
		$encodings = $this->getEncodingsByKey($key);

		foreach ($encodings as $current_encoding) {
			if ($current_encoding['width'] == $width &&
				$current_encoding['template']['format']['key'] != 'original') {
				$encoding = $current_encoding;
				break;
			}
		}

		return $encoding;
	}

	// }}}
	// {{{ public function updateMedia()

	/**
	 * Updates the properties of a single existing media item
	 *
	 * See {@link http://developer.longtailvideo.com/botr/system-api/methods/videos/update.html}
	 *
	 * @param todo
	 *
	 * @return ?
	 */
	public function updateMedia(SiteBotrMedia $media, array $options = array())
	{
		return $this->updateMediaByKey($media->key, $options);
	}

	// }}}
	// {{{ public function updateMediaAddTags()

	public function updateMediaAddTags(SiteBotrMedia $media,
		array $tags = array())
	{
		return $this->updateMediaAddTagsByKey($media->key, $tags);
	}

	// }}}
	// {{{ public function updateMediaReplaceTags()

	public function updateMediaReplaceTags(SiteBotrMedia $media,
		array $tags = array())
	{
		return $this->updateMediaReplaceTagsByKey($media->key, $tags);
	}

	// }}}
	// {{{ public function updateMediaRemoveTags()

	public function updateMediaRemoveTags(SiteBotrMedia $media,
		array $tags = array())
	{
		return $this->updateMediaRemoveTagsByKey($media->key, $tags);
	}

	// }}}
	// {{{ public function updateMediaAddTagsByKey()

	public function updateMediaAddTagsByKey($key, array $tags = array())
	{
		// exit early if we're trying to save the media with no new tags.
		if (count($tags) === 0)
			return true;

		$media = $this->getMediaByKey($key);
		// don't allow tags with whitespace.
		$current_tags = explode(',', str_replace(' ', '', $media['tags']));
		$tags         = array_merge($tags, $current_tags);

		$options = array(
			'tags' => implode(',', $tags),
			);

		return $this->updateMediaByKey($key, $options);
	}

	// }}}
	// {{{ public function updateMediaReplaceTagsByKey()

	public function updateMediaReplaceTagsByKey($key, array $tags = array())
	{
		$options = array('tags' => implode(',', $tags));

		return $this->updateMediaByKey($key, $options);
	}

	// }}}
	// {{{ public function updateMediaRemoveTagsByKey()

	public function updateMediaRemoveTagsByKey($key, array $tags = array())
	{
		// exit early if we're trying to remove no tags.
		if (count($tags) === 0)
			return true;

		$media = $this->getMediaByKey($key);
		// don't allow tags with whitespace.
		$current_tags = explode(',', str_replace(' ', '', $media['tags']));
		$tags         = array_diff($current_tags, $tags);

		$options = array(
			'tags' => implode(',', $tags),
			);

		return $this->updateMediaByKey($key, $options);
	}

	// }}}
	// {{{ public function updateMediaByKey()

	/**
	 * Updates the properties of a single existing media item
	 *
	 * See {@link http://developer.longtailvideo.com/botr/system-api/methods/videos/update.html}
	 *
	 * @param todo
	 *
	 * @return ?
	 */
	public function updateMediaByKey($key, array $options = array())
	{
		// exit early if we're trying to save the media with no new information.
		if (count($options) === 0)
			return true;

		$valid_options = array(
			'title',
			'tags',
			'description',
			'author',
			'date',
			'link',
			'custom',
			);

		$settings = array(
			'video_key' => $key,
			);

		foreach ($options as $key => $value) {
			if (array_search($key, $valid_options) !== false) {
				if ($key == 'custom') {
					foreach ($value as $custom_id => $custom_value) {
						$settings[$key.'.'.$custom_id] = $custom_value;
					}
				} else {
					$settings[$key] = $value;
				}
			} else {
				throw new SiteBotrMediaToasterException(
					sprintf('updateMedia() does not support %s option', $key));
			}
		}

		$media = $this->callBackend('/videos/update', $settings);

		return ($media['status'] == 'ok');
	}

	// }}}
	// {{{ public function getPlayers()

	public function getPlayers()
	{
		$players = $this->callBackend('/players/list');

		return $players;
	}

	// }}}

	// encoding templates
	// {{{ public function getEncodingProfiles()

	public function getEncodingProfiles()
	{
		$profiles = $this->callBackend('/accounts/templates/list');

		return $profiles['templates'];
	}

	// }}}
	// {{{ public function createEncodingProfile()

	/**
	 * Creates a new encoding profile
	 *
	 * See {@link http://developer.longtailvideo.com/botr/system-api/methods/accounts/templates/create.html}
	 *
	 * @param todo
	 *
	 * @return ?
	 */
	public function createEncodingProfile($name, $format, $video_quality = 5,
		$audio_quality = 5, $width = 320, $upscale = false, $default = 'none')
	{
		$settings = array(
			'name'          => $name,
			'format_key'    => $format,
			'video_quality' => $video_quality,
			'audio_quality' => $audio_quality,
			'width'         => $width,
			'upscale'       => ($upscale) ? 'true' : 'false',
			'default'       => $default,
			);

		$profile = $this->callBackend('/accounts/templates/create', $settings);

		if ($profile['status'] != 'ok')
			return false;

		return $profile['template']['key'];
	}

	// }}}
	// {{{ public function deleteEncodingProfile()

	/**
	 * Deletes an encoding profile
	 *
	 * See {@link http://developer.longtailvideo.com/botr/system-api/methods/accounts/templates/delete.html}
	 *
	 * @param profile_id string string id of the encoding profile to delete.
	 *
	 * @return ?
	 */
	public function deleteEncodingProfile($profile_id)
	{
		$settings = array(
			'template_key' => $profile_id,
			);

		$response = $this->callBackend('/accounts/templates/delete', $settings);

		return ($response['status'] == 'ok');
	}

	// }}}

	// Content Display Methods
	// {{{ public function getMediaPlayer()

	public function getMediaPlayer(SiteBotrMedia $media,
		SiteBotrMediaPlayer $player)
	{
		return $this->getMediaPlayerByKeys($media->key, $player->key);
	}

	// }}}
	// {{{ public function getMediaPlayerByKeys()

	public function getMediaPlayerByKeys($media_key, $player_key)
	{
		$path = sprintf('players/%s-%s.js', $media_key, $player_key);
		$src  = $this->getContentPath($path);

		$script = new SwatHtmlTag('script');
		$script->src = $src;

		// explicitly open and close the script tag. The self-closing script tag
		// causes problems with the Botr javascript, and caused content on the
		// page to disappear in Firefox.
		ob_start();
		$script->open();
		$script->close();

		return ob_get_clean();
	}

	// }}}
	// {{{ public function getMediaThumbnail()

	public function getMediaThumbnail(SiteBotrMedia $media, $width = 120)
	{
		return $this->getMediaThumbnailByKey($media->key, $width);
	}

	// }}}
	// {{{ public function getMediaThumbnailByKey()

	public function getMediaThumbnailByKey($media_key, $width = 120)
	{
		// valid widths are 40 / 120 / 320 / 480 / 640 / 720
		if ($width == null) {
			// default is the original size of the media.
			$path = 'thumbs/%s.jpg';
		} else {
			$path = 'thumbs/%s-%s.jpg';
		}

		$path = sprintf($path,
			$media_key,
			$width);

		// content signing isn't necessary for media thumbnails.
		return $this->getContentPath($path);
	}

	// }}}
	// {{{ public function getMediaThumbnailTag()

	public function getMediaThumbnailTag(SiteBotrMedia $media, $width = 120)
	{
		return $this->getMediaThumbnailTagByKey($media->key, $width);
	}

	// }}}
	// {{{ public function getMediaThumbnailTagByKey()

	public function getMediaThumbnailTagByKey($media_key, $width = 120)
	{
		$img_tag = new SwatHtmlTag('img');
		$img_tag->src = $this->getMediaThumbnailByKey($media_key, $width);

		return $img_tag;
	}

	// }}}
	// {{{ public function getMediaDownload()

	/**
	 * Gets the direct download link for a media item.
	 *
	 * See {@link http://developer.longtailvideo.com/botr/system-api/urls/videos.html}
	 *
	 * @param SiteBotrMedia The media to download
	 * @param SiteBotrMediaEncoding The encoded version of the media to
	 *                      download.
	 *
	 * @return string The uri of the direct download.
	 */
	public function getMediaDownload(SiteBotrMedia $media,
		SiteBotrMediaEncoding $encoding)
	{
		$binding   = $media->getEncodingBinding($encoding->shortname);
		$extension = $binding->media_type->extension;

		return $this->getMediaDownloadByKeys($media->key, $encoding->key,
			$extension);
	}

	// }}}
	// {{{ public function getMediaDownloadByKeys()

	/**
	 * Gets the direct download link for a media item.
	 *
	 * See {@link http://developer.longtailvideo.com/botr/system-api/urls/videos.html}
	 *
	 * @param media_key
	 * @param encoding_key
	 * @param extension
	 *
	 * @return string The uri of the direct download.
	 */
	public function getMediaDownloadByKeys($media_key, $encoding_key,
		$extension = 'mp4')
	{
		$path = sprintf('videos/%s-%s.%s',
			$media_key,
			$encoding_key,
			$extension);

		return $this->getContentPath($path);
	}

	// }}}
	// {{{ public function getMediaPreviewLink()

	public function getMediaPreviewLink(SiteBotrMedia $media,
		SiteBotrMediaPlayer $player)
	{
		return $this->getMediaPreviewLinkByKeys($media->key, $player->key);
	}

	// }}}
	// {{{ public function getMediaPreviewLinkByKeys()

	public function getMediaPreviewLinkByKeys($media_key, $player_key)
	{
		$path = sprintf('previews/%s-%s',
			$media_key,
			$player_key);

		return $this->getContentPath($path);
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	public function getHtmlHeadEntrySet()
	{
		return $this->html_head_entry_set;
	}

	// }}}

	// helper methods.
	// {{{ public static function getContentSignature()

	public static function getContentSignature($path, $expires, $secret)
	{
		return md5(sprintf('%s:%s:%s',
			$path,
			$expires,
			$secret));
	}

	// }}}
	// {{{ protected function getContentPath()

	protected function getContentPath($path)
	{
		$query_string = null;

		if ($this->content_signing === true) {
			$expires      = $this->getExpiry();
			$signature    = self::getContentSignature($path, $expires,
				$this->app->config->botr->secret);

			$query_string = sprintf('?exp=%s&sig=%s',
				$expires,
				$signature);
		}

		$base = ($this->app instanceof SiteWebApplication &&
			$this->app->isSecure()) ?
			$this->app->config->botr->secure_base :
			$this->app->config->botr->base;

		return $base.$path.$query_string;
	}

	// }}}
	// {{{ protected function getExpiry()

	protected function getExpiry()
	{
		$expiry = ($this->content_public === true) ?
			$this->public_content_expiry : $this->private_content_expiry;

		return strtotime($expiry);
	}

	// }}}
	// {{{ private function callBackend()

	private function callBackend($method, array $parameters = null)
	{
		if ($this->backend === null) {
			$this->setupBackend();
		}

		$response = $this->backend->call($method, $parameters);

		$this->handleErrors($response);

		return $response;
	}

	// }}}
	// {{{ private function setupBackend()

	private function setupBackend()
	{
		$this->backend = new BotrAPI($this->app->config->botr->key,
			$this->app->config->botr->secret);
	}

	// }}}
	// {{{ private function handleErrors()

	private function handleErrors($response)
	{
		$message = null;

		// all api responses should return an array. if we receive something
		// else back, var_dump it so we know what it is.
		if (is_array($response) == false) {
			ob_start();
			var_dump($response);
			$message = sprintf('Unexpected Response: %s',
				ob_get_clean());
		} elseif ($response['status'] == 'error') {
			$message = sprintf("%s\n\nCode: %s\n\nMessage:\n%s",
				$response['title'],
				$response['code'],
				$response['message']);
		}

		if ($message != null) {
			throw new SiteBotrMediaToasterException($message);
		}
	}

	// }}}
}

?>