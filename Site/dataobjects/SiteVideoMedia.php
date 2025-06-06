<?php

/**
 * A video-specific media object.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property string                               $key
 * @property int                                  $scrubber_image_count
 * @property bool                                 $has_hls
 * @property SiteVideoImage                       $image
 * @property SiteVideoMediaSet                    $media_set
 * @property SiteVideoScrubberImage               $scrubber_image
 * @property SiteVideoMediaEncodingBindingWrapper $video_encoding_bindings
 */
class SiteVideoMedia extends SiteMedia
{
    /**
     * Unique string key.
     *
     * @var string
     */
    public $key;

    /**
     * Scrubber image count.
     *
     * @var int
     */
    public $scrubber_image_count;

    /**
     * Has HLS encodings.
     *
     * @var bool
     */
    public $has_hls;

    public function getHumanFileType($encoding_shortname = null)
    {
        if ($encoding_shortname === null) {
            $binding = $this->getLargestVideoEncodingBinding();

            if ($binding === null) {
                throw new SiteException(sprintf(
                    'Encoding “%s” does not exist for media “%s”.',
                    $encoding_shortname,
                    $this->id
                ));
            }

            $file_type = $binding->getHumanFileType();
        } else {
            $file_type = parent::getHumanFileType($encoding_shortname);
        }

        return $file_type;
    }

    public function getFormattedFileSize($encoding_shortname = null)
    {
        if ($encoding_shortname === null) {
            $binding = $this->getLargestVideoEncodingBinding();

            if ($binding === null) {
                throw new SiteException(sprintf(
                    'Encoding “%s” does not exist for media “%s”.',
                    $encoding_shortname,
                    $this->id
                ));
            }

            $file_size = $binding->getFormattedFileSize();
        } else {
            $file_size = parent::getFormattedFileSize($encoding_shortname);
        }

        return $file_size;
    }

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

    public function getSmallestVideoEncodingBinding()
    {
        $smallest = null;

        foreach ($this->encoding_bindings as $binding) {
            // Bindings with no, or negative width are not video bindings.
            if ($binding->width <= 0) {
                continue;
            }

            if ((($smallest === null) && ($binding->width !== null))
                || (($smallest !== null)
                    && ($binding->width < $smallest->width))) {
                $smallest = $binding;
            }
        }

        return $smallest;
    }

    public function getDefaultAudioEncoding()
    {
        $audio = null;

        foreach ($this->encoding_bindings as $binding) {
            // Return first encoding that has an audio mime type. This can be
            // improved in the future.
            if (mb_strpos($binding->media_type->mime_type, 'audio') !== false) {
                $audio = $binding;
                break;
            }
        }

        return $audio;
    }

    public function getMediaPlayer(SiteApplication $app)
    {
        $jwplayer = $this->getMediaPlayerDisplay();
        $jwplayer->setMedia($this);
        $jwplayer->swf_uri = 'packages/jwplayer/jwplayer.flash.swf';
        $jwplayer->key = $app->config->jwplayer->key;

        $jwplayer->menu_title = $app->config->site->title;
        $jwplayer->menu_link = $app->getBaseHref();

        if ($app->session->isActive()) {
            $jwplayer->setSession($app->session);
        }

        $expires = ($this->media_set->private) ? '1 day' : null;

        if ($this->has_hls) {
            $jwplayer->addSource(
                $app->cdn->getUri(
                    $this->getHlsFilePath(),
                    $expires
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
                        $expires
                    ),
                    $binding->width,
                    $binding->height . 'p'
                );
            }
        }

        if ($this->image !== null) {
            $dimensions = $this->image->image_set->dimensions;
            foreach ($dimensions as $dimension) {
                if ($this->image->hasDimension($dimension->shortname)) {
                    $jwplayer->addImage(
                        $this->image->getUri($dimension->shortname),
                        $this->image->getWidth($dimension->shortname)
                    );
                }
            }
        }

        return $jwplayer;
    }

    public function getMediaPlayerDisplay()
    {
        return new SiteJwPlayerMediaDisplay('video' . $this->id);
    }

    /**
     * Loads a media object from its key.
     *
     * @param string $key       the key of the media to load
     * @param mixed  $file_base
     *
     * @return SiteJwPlayerMediaDisplay
     *
     * @deprecated Key will be null on newly-uploaded videos.
     *             Use getMediaPlayerByPathKey instead.
     */
    public function getMediaPlayerByKey(
        SiteApplication $app,
        $key,
        $file_base = 'media'
    ) {
        if ($this->db === null) {
            $this->setDatabase($app->db);
        }

        if (!$this->loadByKey($key)) {
            throw new SwatException('Video not found for key: ' . $key);
        }

        $this->setFileBase($file_base);

        return $this->getMediaPlayer($app);
    }

    /**
     * Loads a media object from its path key.
     *
     * @param string $key       the key of the media to load
     * @param mixed  $file_base
     *
     * @return SiteJwPlayerMediaDisplay
     */
    public function getMediaPlayerByPathKey(
        SiteApplication $app,
        $key,
        $file_base = 'media'
    ) {
        if ($this->db === null) {
            $this->setDatabase($app->db);
        }

        if (!$this->loadByPathKey($key)) {
            throw new SwatException('Video not found for path key: ' . $key);
        }

        $this->setFileBase($file_base);

        return $this->getMediaPlayer($app);
    }

    public function getMimeTypes()
    {
        $types = [];
        foreach ($this->encoding_bindings as $binding) {
            if ($binding->width !== null && $binding->width > 0) {
                $mime_type = $binding->media_type->mime_type;
                $types[$mime_type] = $mime_type;
            }
        }

        return $types;
    }

    public function getScrubberImageInterval()
    {
        $count = ($this->scrubber_image_count > 0) ?
            $this->scrubber_image_count : $this->getDefaultScrubberImageCount();

        return $this->duration / $count;
    }

    public function getDefaultScrubberImageCount()
    {
        // only used for generating scrubber images. For displaying them,
        // use SiteVideoMedia::$scrubber_image_count
        return 100;
    }

    public function getScrubberImageWidth()
    {
        return 130;
    }

    public function getFileDirectory($encoding_shortname)
    {
        $directory = parent::getFileDirectory($encoding_shortname);

        if ($this->has_hls) {
            $directory = implode(
                DIRECTORY_SEPARATOR,
                [$this->getFileBase(), $this->path_key, 'full']
            );
        }

        return $directory;
    }

    public function getFilename($encoding_shortname)
    {
        $binding = $this->getEncodingBinding($encoding_shortname);

        if ($this->getMediaSet()->obfuscate_filename) {
            $filename = $this->filename;
        } elseif ($this->has_hls) {
            $filename = $encoding_shortname;
        } else {
            $filename = $this->path_key;
        }

        return sprintf(
            '%s.%s',
            $filename,
            $binding->media_type->extension
        );
    }

    public function getHlsFilePath()
    {
        $items = [$this->getFileBase(), $this->path_key, 'hls', 'index.m3u8'];

        return implode(DIRECTORY_SEPARATOR, $items);
    }

    public function getUriSuffix($encoding_shortname)
    {
        $suffix = parent::getUriSuffix($encoding_shortname);

        if ($this->has_hls) {
            $suffix = sprintf(
                '%s/%s/%s',
                $this->path_key,
                'full',
                $this->getFilename($encoding_shortname)
            );

            if ($this->getUriBase() != '') {
                $suffix = $this->getUriBase() . '/' . $suffix;
            }
        }

        return $suffix;
    }

    protected function init()
    {
        parent::init();

        $this->registerInternalProperty(
            'image',
            SwatDBClassMap::get(SiteVideoImage::class)
        );

        $this->registerInternalProperty(
            'scrubber_image',
            SwatDBClassMap::get(SiteVideoScrubberImage::class)
        );

        $this->registerInternalProperty(
            'media_set',
            SwatDBClassMap::get(SiteVideoMediaSet::class)
        );
    }

    protected function getMediaEncodingBindingWrapperClass()
    {
        return SwatDBClassMap::get(SiteVideoMediaEncodingBindingWrapper::class);
    }

    protected function getMediaEncodingBindingsOrderBy()
    {
        // Load encodings by size, but put nulls first since those would be
        // audio only encodings.
        return 'order by width asc nulls first';
    }

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

    protected function deleteCdnFiles()
    {
        if ($this->getUriBase() != '') {
            $path = [$this->getUriBase(), $this->path_key];

            $class_name = SwatDBClassMap::get(SiteMediaCdnTask::class);
            $task = new $class_name();
            $task->setDatabase($this->db);
            $task->operation = 'delete';
            $task->file_path = implode(DIRECTORY_SEPARATOR, $path);
            $task->save();
        }
    }
}
