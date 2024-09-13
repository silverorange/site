<?php

/**
 * Display class for SiteMedia using JWPlayer.
 *
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteJwPlayerMediaDisplay extends SwatControl
{
    public $key;
    public $valid_mime_types;
    public $start_position = 0;
    public $record_end_point = false;
    public $on_complete_message;
    public $swf_uri;
    public $menu_title;
    public $menu_link;
    public $playback_rate_controls;
    public $has_captions = false;

    /*
     * Whether or not to show the on-complete-message when the video loads
     *
     * This is useful if you want to remind the user they've seen the video
     * before.
     *
     * @var boolean
     */
    public $display_on_complete_message_on_load = false;

    protected $media;
    protected $sources = [];
    protected $images = [];
    protected $session;
    protected $aspect_ratio = [];
    protected $skin;
    protected $stretching;
    protected $vtt_uri;
    protected $mute = false;
    protected $auto_start = false;
    protected $controls = true;
    protected $repeat = false;
    protected $container_id;
    protected $player_id;
    protected $javascript_variable_name;

    /**
     * Creates a new widget.
     *
     * @param string $id a non-visible unique id for this widget
     */
    public function __construct($id = null)
    {
        parent::__construct();

        $yui = new SwatYUI(['swf', 'event', 'cookie']);
        $this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

        $this->addJavascript('packages/jwplayer/jwplayer.js');
        $this->addJavascript(
            'packages/site/javascript/site-jw-player-media-display.js'
        );

        $this->addStylesheet(
            'packages/site/styles/site-jw-player-media-display.css'
        );
    }

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

        if ($this->vtt_uri === null) {
            $this->setVttUri('vtt/' . $media->id . '.vtt');
        }
    }

    public function setSkin($skin)
    {
        $this->skin = $skin;
    }

    public function setAspectRatio($width, $height)
    {
        $this->aspect_ratio = ['width' => $width, 'height' => $height];
    }

    public function setStretching($fit)
    {
        $valid_fits = ['none', 'exactfit', 'uniform', 'fill'];

        if ($fit !== null && $fit !== '' && !in_array($fit, $valid_fits)) {
            throw new SwatException('Stretching not valid');
        }

        $this->stretching = $fit;
    }

    public function addSource($uri, $width = '', $label = '')
    {
        $source = [];
        $source['uri'] = $uri;
        $source['width'] = $width;
        $source['label'] = $label;

        $this->sources[] = $source;
    }

    public function addImage($uri, $width)
    {
        $image = [];
        $image['uri'] = $uri;
        $image['width'] = $width;
        $this->images[] = $image;
    }

    public function setSession(SiteSessionModule $session)
    {
        $this->session = $session;
    }

    public function setVttUri($uri)
    {
        $this->vtt_uri = $uri;
    }

    public function setMute($mute)
    {
        $this->mute = (bool) $mute;
    }

    public function setAutoStart($auto_start)
    {
        $this->auto_start = (bool) $auto_start;
    }

    public function setControls($controls)
    {
        $this->controls = (bool) $controls;
    }

    public function setRepeat($repeat)
    {
        $this->repeat = (bool) $repeat;
    }

    public function display()
    {
        parent::display();

        if ($this->media === null) {
            throw new SwatException('Media must be specified');
        }
        if ($this->media->media_set->private) {
            if ($this->session === null) {
                throw new SwatException('Private video, session must be set');
            }
            if (!$this->session->isActive()) {
                throw new SwatException(
                    'Private video, session must be active'
                );
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
            $ajax = new XML_RPCAjax();
            $this->html_head_entry_set->addEntrySet(
                $ajax->getHtmlHeadEntrySet()
            );
        }

        if ($this->key !== null) {
            Swat::displayInlineJavaScript(sprintf(
                'jwplayer.key = %s;',
                SwatString::quoteJavaScriptString($this->key)
            ));
        }

        echo '<div class="video-player-container">';

        $container_div = new SwatHtmlTag('div');
        $container_div->class = 'video-player';

        // Safari (iOS and OS X) will show a CC icon even if the SMIL file
        // only contains the scrubber image. Us a css class to hide it.
        $container_div->class .= ($this->has_captions)
            ? ' has-captions'
            : ' no-captions';

        $container_div->id = $this->getContainerId();
        $container_div->open();

        $player_div = new SwatHtmlTag('div');
        $player_div->id = $this->getPlayerId();
        $player_div->open();
        $player_div->close();

        $container_div->close();

        echo '</div>';

        Swat::displayInlineJavaScript($this->getJavascript());
    }

    public function getJavascriptVariableName()
    {
        if ($this->javascript_variable_name == '') {
            $this->setJavascriptVariableName(
                sprintf('site_%s_media', $this->media->id)
            );
        }

        return $this->javascript_variable_name;
    }

    public function setJavascriptVariableName($javascript_variable_name)
    {
        $this->javascript_variable_name = $javascript_variable_name;
    }

    public function getContainerId()
    {
        if ($this->container_id == '') {
            $this->setContainerId('media_display_' . $this->media->id);
        }

        return $this->container_id;
    }

    public function setContainerId($container_id)
    {
        $this->container_id = $container_id;
    }

    public function getPlayerId()
    {
        if ($this->player_id == '') {
            $this->setPlayerId('media_display_container_' . $this->media->id);
        }

        return $this->player_id;
    }

    public function setPlayerId($player_id)
    {
        $this->player_id = $player_id;
    }

    protected function getJavascript()
    {
        $javascript = sprintf(
            "\tvar %s = new %s(%d, %s);\n",
            $this->getJavascriptVariableName(),
            $this->getJavascriptClassName(),
            $this->media->id,
            SwatString::quoteJavaScriptString($this->getContainerId())
        );

        $javascript .= sprintf(
            "\t%s.duration = %d;\n",
            $this->getJavascriptVariableName(),
            $this->media->duration
        );

        $javascript .= sprintf(
            "\t%s.aspect_ratio = [%d, %d];\n",
            $this->getJavascriptVariableName(),
            $this->aspect_ratio['width'],
            $this->aspect_ratio['height']
        );

        if ($this->media->getInternalValue('scrubber_image') !== null) {
            $javascript .= sprintf(
                "\t%s.vtt_uri = %s;\n",
                $this->getJavascriptVariableName(),
                SwatString::quoteJavaScriptString(
                    $this->getVttUri()
                )
            );
        }

        if ($this->swf_uri !== null) {
            $javascript .= sprintf(
                "\t%s.swf_uri = %s;\n",
                $this->getJavascriptVariableName(),
                SwatString::quoteJavaScriptString($this->swf_uri)
            );
        }

        foreach ($this->sources as $source) {
            $javascript .= sprintf(
                "\t%s.addSource(%s, %s, %s);\n",
                $this->getJavascriptVariableName(),
                SwatString::quoteJavaScriptString($source['uri']),
                ($source['width'] == '') ? "''" : $source['width'],
                SwatString::quoteJavaScriptString($source['label'])
            );
        }

        if ($this->skin !== null) {
            $javascript .= sprintf(
                "\t%s.skin = %s;\n",
                $this->getJavascriptVariableName(),
                SwatString::quoteJavaScriptString($this->skin)
            );
        }

        if ($this->stretching !== null) {
            $javascript .= sprintf(
                "\t%s.stretching = %s;\n",
                $this->getJavascriptVariableName(),
                SwatString::quoteJavaScriptString($this->stretching)
            );
        }

        if ($this->playback_rate_controls !== null) {
            $javascript .= sprintf(
                "\t%s.playback_rate_controls = %s;\n",
                $this->getJavascriptVariableName(),
                $this->playback_rate_controls ? 'true' : 'false'
            );
        }

        foreach ($this->images as $image) {
            $javascript .= sprintf(
                "\t%s.addImage(%s, %d);\n",
                $this->getJavascriptVariableName(),
                SwatString::quoteJavaScriptString($image['uri']),
                $image['width']
            );
        }

        $javascript .= sprintf(
            "\t%s.start_position = %d;\n",
            $this->getJavascriptVariableName(),
            $this->start_position
        );

        if ($this->session !== null && $this->session->isActive()) {
            $javascript .= sprintf(
                "\t%s.record_end_point = %s;\n",
                $this->getJavascriptVariableName(),
                ($this->record_end_point) ? 'true' : 'false'
            );
        }

        if ($this->on_complete_message !== null) {
            $javascript .= sprintf(
                "\t%s.on_complete_message = %s;\n",
                $this->getJavascriptVariableName(),
                SwatString::quoteJavascriptString($this->on_complete_message)
            );
        }

        $javascript .= sprintf(
            "\t%s.upgrade_message = %s;\n",
            $this->getJavascriptVariableName(),
            SwatString::quoteJavascriptString(
                $this->getBrowserNotSupportedMessage(
                    $this->valid_mime_types
                )
            )
        );

        if ($this->display_on_complete_message_on_load) {
            $javascript .= sprintf(
                "\t%s." .
                "display_on_complete_message_on_load = true;\n",
                $this->getJavascriptVariableName()
            );
        }

        if ($this->menu_link !== null) {
            $javascript .= sprintf(
                "\t%s.menu_link = %s;\n",
                $this->getJavascriptVariableName(),
                SwatString::quoteJavascriptString($this->menu_link)
            );
        }

        if ($this->menu_title !== null) {
            $javascript .= sprintf(
                "\t%s.menu_title = %s;\n",
                $this->getJavascriptVariableName(),
                SwatString::quoteJavascriptString($this->menu_title)
            );
        }

        foreach ($this->valid_mime_types as $mime_type) {
            $javascript .= sprintf(
                "\t%s.addValidMimeType(%s);\n",
                $this->getJavascriptVariableName(),
                SwatString::quoteJavaScriptString($mime_type)
            );
        }

        if ($this->mute) {
            $javascript .= sprintf(
                "\t%s.mute = true;\n",
                $this->getJavascriptVariableName()
            );
        }

        if ($this->auto_start) {
            $javascript .= sprintf(
                "\t%s.auto_start = true;\n",
                $this->getJavascriptVariableName()
            );
        }

        if (!$this->controls) {
            $javascript .= sprintf(
                "\t%s.controls = false;\n",
                $this->getJavascriptVariableName()
            );
        }

        if ($this->repeat) {
            $javascript .= sprintf(
                "\t%s.repeat = true;\n",
                $this->getJavascriptVariableName()
            );
        }

        return $javascript;
    }

    protected function getJavascriptClassName()
    {
        return 'SiteJwPlayerMediaDisplay';
    }

    protected function getVttUri()
    {
        return $this->vtt_uri;
    }

    protected function getBrowserNotSupportedMessage($mime_types)
    {
        $codecs = [];
        foreach ($mime_types as $type) {
            $exploded_type = explode('/', $type);
            $codecs[] = array_pop($exploded_type);
        }

        return sprintf(
            'Videos on this site require either ' .
            '<a href="https://en.wikipedia.org/wiki/HTML5_video" ' .
            'target="_blank">HTML5 video support</a> (%s %s) or ' .
            '<a href="https://get.adobe.com/flashplayer/" target="_blank">' .
            'Adobe Flash Player</a> (version 18 or higher). ' .
            'Please upgrade your browser and try again.',
            SwatString::toList($codecs, 'or'),
            ngettext('codec', 'codecs', count($codecs))
        );
    }
}
