<?php

require_once 'Swat/SwatControl.php';
require_once 'Site/dataobjects/SiteMedia.php';
require_once 'Site/dataobjects/SiteMediaEncoding.php';

/**
 * Display class for SiteMedia using JWPlayer
 *
 * @package   Site
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteJwPlayerMediaDisplay extends SwatControl
{
	// {{{ public static properties

	public static $location_identifier;

	// }}}
	// {{{ public properties

	public $key;
	public $valid_mime_types;
	public $start_position = 0;
	public $record_end_point = false;
	public $on_complete_message = null;
	public $swf_uri = null;
	public $space_to_pause = false;
	public $menu_title = null;
	public $menu_link = null;

	/*
	 * Whether or not to show the on-complete-message when the video loads
	 *
	 * This is useful if you want to remind the user they've seen the video
	 * before.
	 *
	 * @var boolean
	 */
	public $display_on_complete_message_on_load = false;

	// }}}
	// {{{ protected properties

	protected $media;
	protected $sources = array();
	protected $images = array();
	protected $session;
	protected $aspect_ratio = array();
	protected $skin;
	protected $stretching;
	protected $manifest_uri;
	protected $vtt_uri;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new widget
	 *
	 * @param string $id a non-visible unique id for this widget.
	 */
	public function __construct($id = null)
	{
		parent::__construct();

		$yui = new SwatYUI(array('swf', 'event', 'cookie'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavascript('packages/site/javascript/jwplayer.js');
		$this->addJavascript(
			'packages/site/javascript/site-jw-player-media-display.js'
		);

		$this->addStylesheet(
			'packages/site/styles/site-jw-player-media-display.css'
		);
	}

	// }}}
	// {{{ public function setMedia()

	public function setMedia(SiteVideoMedia $media)
	{
		$this->media = $media;

		$binding = $media->getLargestVideoEncodingBinding();

		if ($binding === null) {
			throw new SiteException('No media encodings found');
		}

		if (count($this->aspect_ratio) == 0) {
			$this->setAspectRatio($binding->width, $binding->height);
		}

		if ($this->skin === null) {
			$this->setSkin($media->media_set->skin);
		}

		if ($this->manifest_uri === null) {
			$this->setManifestUri('smil/'.$media->id.'.smil');
		}

		if ($this->vtt_uri === null) {
			$this->setVttUri('vtt/'.$media->id.'.vtt');
		}
	}

	// }}}
	// {{{ public function setSkin()

	public function setSkin($skin)
	{
		if (mb_strpos($skin, '.xml') === false) {
			if ($skin === null) {
				$skin = 'six';
			}

			$valid_skins = array(
				'six',
				'bekle',
				'modieus',
				'glow',
				'five',
				'beelden',
				'stormtrooper',
				'vapor',
				'roundster',
			);

			if (!in_array($skin, $valid_skins)) {
				throw new SwatException('Skin not valid');
			} else {
				$this->skin = 'packages/site/javascript/jwplayer-skins/'.$skin.'.xml';
			}
		} else {
			$this->skin = $skin;
		}
	}

	// }}}
	// {{{ public function setAspectRatio()

	public function setAspectRatio($width, $height)
	{
		$this->aspect_ratio = array('width' => $width, 'height' => $height);
	}

	// }}}
	// {{{ public function setStretching()

	public function setStretching($fit)
	{
		$valid_fits = array(
			'none',
			'exactfit',
			'uniform',
			'fill',
		);

		if ($fit !== null && $fit !== '' && !in_array($fit, $valid_fits)) {
			throw new SwatException('Stretching not valid');
		}

		$this->stretching = $fit;
	}

	// }}}
	// {{{ public function addSource()

	public function addSource($uri, $width = '', $label = '')
	{
		$source          = array();
		$source['uri']   = $uri;
		$source['width'] = $width;
		$source['label'] = $label;

		$this->sources[] = $source;
	}

	// }}}
	// {{{ public function addImage()

	public function addImage($uri, $width)
	{
		$image          = array();
		$image['uri']   = $uri;
		$image['width'] = $width;
		$this->images[] = $image;
	}

	// }}}
	// {{{ public function setSession()

	public function setSession(SiteSessionModule $session)
	{
		$this->session = $session;
	}

	// }}}
	// {{{ public function setManifestUri()

	public function setManifestUri($uri)
	{
		$this->manifest_uri = $uri;
	}

	// }}}
	// {{{ public function setVttUri()

	public function setVttUri($uri)
	{
		$this->vtt_uri = $uri;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		parent::display();

		if ($this->media === null) {
			throw new SwatException('Media must be specified');
		} elseif ($this->media->media_set->private) {
			if ($this->session === null) {
				throw new SwatException('Private video, session must be set');
			} elseif (!$this->session->isActive()) {
				throw new SwatException(
					'Private video, session must be active');
			}
		}

		if ($this->session !== null && $this->media->media_set->private) {
			if (!isset($this->session->media_access)) {
				$this->session->media_access = new ArrayObject();
			}

			$this->session->media_access[$this->media->id] = true;
		}

		if ($this->valid_mime_types === null) {
			$this->valid_mime_types = $this->media->getMimeTypes();
		}

		if ($this->record_end_point) {
			require_once 'XML/RPCAjax.php';
			$ajax = new XML_RPCAjax();
			$this->html_head_entry_set->addEntrySet(
				$ajax->getHtmlHeadEntrySet());
		}

		if ($this->key !== null) {
			static $key_included = false;

			if (!$key_included) {
				$key_included = true;
				Swat::displayInlineJavaScript(sprintf('jwplayer.key = %s;',
					SwatString::quoteJavaScriptString($this->key)));
			}
		}

		echo '<div class="video-player-container">';
		echo '<div class="video-player" id="media_display_'.
			$this->media->id.'">';

		echo '<div id="media_display_container_'.$this->media->id.'"></div>';

		echo '</div>';
		echo '</div>';

		Swat::displayInlineJavaScript($this->getJavascript());
	}

	// }}}
	// {{{ public function getJavascriptVariableName()

	public function getJavascriptVariableName()
	{
		return sprintf('site_%s_media', $this->media->id);
	}

	// }}}
	// {{{ protected function getJavascript()

	protected function getJavascript()
	{
		$javascript = sprintf("\tvar %s = new %s(%d);\n",
			$this->getJavascriptVariableName(),
			$this->getJavascriptClassName(),
			$this->media->id);

		$javascript.= sprintf("\t%s.duration = %d;\n",
			$this->getJavascriptVariableName(),
			$this->media->duration);

		$javascript.= sprintf("\t%s.aspect_ratio = [%d, %d];\n",
			$this->getJavascriptVariableName(),
			$this->aspect_ratio['width'],
			$this->aspect_ratio['height']);

		// don't do RTMP when the file has HLS
		if (!$this->media->has_hls) {
			$javascript.= sprintf(
				"\t%s.addSource(%s);\n",
				$this->getJavascriptVariableName(),
				SwatString::quoteJavaScriptString(
					$this->getManifestUri()
				)
			);
		}

		if ($this->media->getInternalValue('scrubber_image') !== null) {
			$javascript.= sprintf("\t%s.vtt_uri = %s;\n",
				$this->getJavascriptVariableName(),
				SwatString::quoteJavaScriptString(
					$this->getVttUri()));
		}

		if ($this->swf_uri !== null) {
			$javascript.= sprintf("\t%s.swf_uri = %s;\n",
				$this->getJavascriptVariableName(),
				SwatString::quoteJavaScriptString($this->swf_uri));
		}

		foreach ($this->sources as $source) {
			$javascript.= sprintf("\t%s.addSource(%s, %s, %s);\n",
				$this->getJavascriptVariableName(),
				SwatString::quoteJavaScriptString($source['uri']),
				($source['width'] == '') ? "''" : $source['width'],
				SwatString::quoteJavaScriptString($source['label']));
		}

		if ($this->skin !== null) {
			$javascript.= sprintf("\t%s.skin = %s;\n",
				$this->getJavascriptVariableName(),
				SwatString::quoteJavaScriptString($this->skin));
		}

		if ($this->stretching !== null) {
			$javascript.= sprintf("\t%s.stretching = %s;\n",
				$this->getJavascriptVariableName(),
				SwatString::quoteJavaScriptString($this->stretching));
		}

		foreach ($this->images as $image) {
			$javascript.= sprintf("\t%s.addImage(%s, %d);\n",
				$this->getJavascriptVariableName(),
				SwatString::quoteJavaScriptString($image['uri']),
				$image['width']);
		}

		$javascript.= sprintf("\t%s.start_position = %d;\n",
			$this->getJavascriptVariableName(),
			$this->start_position);

		if ($this->session !== null && $this->session->isActive()) {
			$javascript.= sprintf("\t%s.record_end_point = %s;\n",
				$this->getJavascriptVariableName(),
				($this->record_end_point) ? 'true' : 'false');
		}

		if ($this->space_to_pause) {
			$javascript.= sprintf("\t%s.space_to_pause = %s;\n",
				$this->getJavascriptVariableName(),
				($this->space_to_pause) ? 'true' : 'false');
		}

		if ($this->on_complete_message !== null) {
			$javascript.= sprintf("\t%s.on_complete_message = %s;\n",
				$this->getJavascriptVariableName(),
				SwatString::quoteJavascriptString($this->on_complete_message));
		}

		$javascript.= sprintf("\t%s.upgrade_message = %s;\n",
			$this->getJavascriptVariableName(),
			SwatString::quoteJavascriptString(
				$this->getBrowserNotSupportedMessage(
					$this->valid_mime_types)));

		if ($this->display_on_complete_message_on_load) {
			$javascript.= sprintf("\t%s.".
				"display_on_complete_message_on_load = true;\n",
				$this->getJavascriptVariableName());
		}

		if ($this->menu_link !== null) {
			$javascript.= sprintf("\t%s.menu_link = %s;\n",
				$this->getJavascriptVariableName(),
				SwatString::quoteJavascriptString($this->menu_link));
		}

		if ($this->menu_title !== null) {
			$javascript.= sprintf("\t%s.menu_title = %s;\n",
				$this->getJavascriptVariableName(),
				SwatString::quoteJavascriptString($this->menu_title));
		}

		// A unqiue location for the user. Used for storing location-specific
		// info such as if RTMP is blocked
		if (self::$location_identifier !== null) {
			$javascript.= sprintf("\t%s.location_identifier = %s;\n",
				$this->getJavascriptVariableName(),
				SwatString::quoteJavascriptString(md5(
					self::$location_identifier)));
		}

		foreach ($this->valid_mime_types as $mime_type) {
			$javascript.= sprintf("\t%s.addValidMimeType(%s);\n",
				$this->getJavascriptVariableName(),
				SwatString::quoteJavaScriptString($mime_type));
		}

		return $javascript;
	}

	// }}}
	// {{{ protected function getJavascriptClassName()

	protected function getJavascriptClassName()
	{
		return 'SiteJwPlayerMediaDisplay';
	}

	// }}}
	// {{{ protected function getManifestUri()

	protected function getManifestUri()
	{
		return $this->manifest_uri;
	}

	// }}}
	// {{{ protected function getVttUri()

	protected function getVttUri()
	{
		return $this->vtt_uri;
	}

	// }}}
	// {{{ protected function getBrowserNotSupportedMessage()

	protected function getBrowserNotSupportedMessage($mime_types)
	{
		$codecs = array();
		foreach ($mime_types as $type) {
			$codecs[] = array_pop(explode('/', $type));
		}

		return sprintf('Videos on this site require either '.
			'<a href="http://en.wikipedia.org/wiki/HTML5_video" '.
			'target="_blank">HTML5 video support</a> (%s %s) or '.
			'<a href="http://get.adobe.com/flashplayer/" target="_blank">'.
			'Adobe Flash Player</a> (version 10 or higher). '.
			'Please upgrade your browser and try again.',
			SwatString::toList($codecs, 'or'),
			ngettext('codec', 'codecs', count($codecs)));
	}

	// }}}
}

?>
