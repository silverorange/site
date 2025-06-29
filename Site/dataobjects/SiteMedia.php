<?php

/**
 * A media object.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @todo      Support MediaSet.obfuscate_filename on process/save.
 *
 * @property int                             $id
 * @property ?string                         $path_key
 * @property ?string                         $title
 * @property ?string                         $filename
 * @property ?string                         $original_filename
 * @property ?string                         $description
 * @property bool                            $downloadable
 * @property ?int                            $duration
 * @property ?SwatDate                       $createdate
 * @property string                          $file_base
 * @property string                          $media_set_shortname
 * @property SiteMediaSet                    $media_set
 * @property SiteMediaEncodingBindingWrapper $encoding_bindings
 */
class SiteMedia extends SwatDBDataObject
{
    /**
     * The base uri for CDN hosted media.
     *
     * @var string
     */
    public static $cdn_base;

    /**
     * The unique identifier of this media.
     *
     * @var int
     */
    public $id;

    /**
     * Path key.
     *
     * @var string
     */
    public $path_key;

    /**
     * Title.
     *
     * @var string
     */
    public $title;

    /**
     * Filename.
     *
     * @var string
     */
    public $filename;

    /**
     * Original Filename.
     *
     * @var string
     */
    public $original_filename;

    /**
     * Description.
     *
     * @var string
     */
    public $description;

    /**
     * Whether or not the media is Downloadable.
     *
     * @var bool
     */
    public $downloadable;

    /**
     * Duration (in seconds).
     *
     * @var int
     */
    public $duration;

    /**
     * The date that this media was created.
     *
     * @var SwatDate
     */
    public $createdate;

    /**
     * @var string
     */
    protected $file_base;

    /**
     * @var string
     */
    protected $media_set_shortname;

    protected function init()
    {
        $this->table = 'Media';
        $this->id_field = 'integer:id';

        $this->registerDateProperty('createdate');

        $this->registerInternalProperty(
            'media_set',
            SwatDBClassMap::get(SiteMediaSet::class)
        );
    }

    /**
     * Returns the title of the media object.
     *
     * @returns string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns the duration of the media in a human readable format.
     *
     * DateIntervals were dismissed because creating 10-20 SwatDate's per page
     * seemed slow and excessive. This is a slightly simplified version of
     * SwatString::toHumanReadableTimePeriod() to allow for custom formatting.
     * It always returns the two largest time parts.
     *
     * @returns string
     */
    public function getFormattedDuration()
    {
        $format = null;
        $hours = null;
        $minutes = null;
        // don't care about micro-seconds.
        $seconds = floor($this->duration);

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

        // use sprintf for padding, because I read somewhere on the internet it
        // was faster than str_pad.
        if ($hours !== null) {
            // Drop the seconds from the format since it seems overkill when we
            // have over an hour of content.
            // 1h, 30m
            if ($minutes !== null) {
                $format = Site::_('%1$sh, %2$02sm');
            } else {
                $format = Site::ngettext(
                    '%1$s hour',
                    '%1$s hours',
                    $hours
                );
            }
        } elseif ($minutes !== null) {
            // 30:30
            $format = Site::_('%2$s:%3$02s');
        } else {
            // 30s
            $format = Site::_('%3$ss');
        }

        return sprintf(
            $format,
            $hours,
            $minutes,
            $seconds
        );
    }

    public function isDownloadable()
    {
        return $this->downloadable;
    }

    public function encodingExists($encoding_shortname)
    {
        $binding = $this->getEncodingBinding($encoding_shortname);

        return $binding instanceof SiteMediaEncodingBinding;
    }

    public function getDownloadUri($encoding_shortname = null, $prefix = null)
    {
        if ($prefix !== null) {
            $prefix .= '/';
        }

        $id = $this->id;
        if ($encoding_shortname !== null) {
            $id .= '-' . $encoding_shortname;
        }

        return sprintf('%smedia%s', $prefix, $id);
    }

    public function getMimeType($encoding_shortname)
    {
        $binding = $this->getEncodingBinding($encoding_shortname);

        if ($binding === null) {
            throw new SiteException(sprintf(
                'Encoding “%s” does not exist for media “%s”.',
                $encoding_shortname,
                $this->id
            ));
        }

        return $binding->media_type->mime_type;
    }

    public function getMimeTypes()
    {
        $types = [];
        foreach ($this->encoding_bindings as $binding) {
            $mime_type = $binding->media_type->mime_type;
            $types[$mime_type] = $mime_type;
        }

        return $types;
    }

    public function getValidMediaTypes()
    {
        $media_set = $this->getMediaSet();
        $types = [];
        foreach ($media_set->encodings as $encoding) {
            $types[$encoding->default_type->id] = $encoding->default_type;
        }

        return $types;
    }

    public function getHumanFileType($encoding_shortname = null)
    {
        if ($encoding_shortname === null) {
            $binding = $this->getLargestEncodingBinding();
        } else {
            $binding = $this->getEncodingBinding($encoding_shortname);
        }

        if ($binding === null) {
            throw new SiteException(sprintf(
                'Encoding “%s” does not exist for media “%s”.',
                $encoding_shortname,
                $this->id
            ));
        }

        return $binding->getHumanFileType();
    }

    public function getFileSize($encoding_shortname = null)
    {
        if ($encoding_shortname === null) {
            $binding = $this->getLargestEncodingBinding();
        } else {
            $binding = $this->getEncodingBinding($encoding_shortname);
        }

        if ($binding === null) {
            throw new SwatException(sprintf(
                'Encoding “%s” does not exist for media “%s”.',
                $encoding_shortname,
                $this->id
            ));
        }

        return $binding->filesize;
    }

    public function getFormattedFileSize($encoding_shortname = null)
    {
        if ($encoding_shortname === null) {
            $binding = $this->getLargestEncodingBinding();
        } else {
            $binding = $this->getEncodingBinding($encoding_shortname);
        }

        if ($binding === null) {
            throw new SiteException(sprintf(
                'Encoding “%s” does not exist for media “%s”.',
                $encoding_shortname,
                $this->id
            ));
        }

        return $binding->getFormattedFileSize();
    }

    public function getLargestEncodingBinding()
    {
        $largest = null;

        foreach ($this->encoding_bindings as $binding) {
            if ($largest === null) {
                $largest = $binding;
            }

            if ($binding->filesize > $largest->filesize) {
                $largest = $binding;
            }
        }

        return $largest;
    }

    public function getEncodingBinding($encoding_shortname)
    {
        $encoding = $this->media_set->getEncodingByShortname(
            $encoding_shortname
        );

        foreach ($this->encoding_bindings as $binding) {
            $id = ($binding->media_encoding instanceof SiteMediaEncoding) ?
                $binding->media_encoding->id : $binding->media_encoding;

            if ($encoding->id === $id) {
                return $binding;
            }
        }

        return null;
    }

    /**
     * Sets the on_cdn column on the media encoding binding.
     *
     * @param bool   $on_cdn             the new value for on_cdn
     * @param string $encoding_shortname the shortname of the encoding to
     *                                   update
     */
    public function setOnCdn($on_cdn, $encoding_shortname)
    {
        $this->checkDB();

        $encoding = $this->media_set->getEncodingByShortname(
            $encoding_shortname
        );

        $sql = sprintf(
            'update MediaEncodingBinding set on_cdn = %s where
			media = %s and media_encoding = %s',
            $this->db->quote($on_cdn, 'boolean'),
            $this->db->quote($this->id, 'integer'),
            $this->db->quote($encoding->id, 'integer')
        );

        SwatDB::exec($this->db, $sql);
    }

    /**
     * Checks whether a media encoding binding exists on the cdn.
     *
     * @param string $encoding_shortname the shortname of the encoding to
     *                                   check
     *
     * @returns boolean
     */
    public function encodingOnCdn($encoding_shortname)
    {
        $on_cdn = false;

        $encoding = $this->getEncodingBinding($encoding_shortname);
        if ($encoding instanceof SiteMediaEncodingBinding) {
            $on_cdn = $encoding->on_cdn;
        }

        return $on_cdn;
    }

    public function load($id)
    {
        $loaded = parent::load($id);

        if ($loaded
            && $this->media_set_shortname !== null
            && $this->media_set->shortname !== $this->media_set_shortname) {
            throw new SiteException('Trying to load media with the wrong ' .
                'media set. This may happen if the wrong wrapper class is ' .
                'used.');
        }

        return $loaded;
    }

    /**
     * Loads a media object from its key.
     *
     * @param string $key the key of the media to load
     *
     * @return bool true if the loading of this media was successful and
     *              false if the media with the given key doesn't exist
     *
     * @deprecated Key will be null on newly-uploaded videos.
     *             Use loadByPathKey instead.
     */
    public function loadByKey($key)
    {
        $this->checkDB();

        $row = null;

        if ($this->table !== null) {
            $sql = sprintf(
                'select * from %s where key = %s',
                $this->table,
                $this->db->quote($key)
            );

            $rs = SwatDB::query($this->db, $sql, null);
            $row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
        }

        if ($row === null) {
            return false;
        }

        $this->initFromRow($row);
        $this->generatePropertyHashes();

        return true;
    }

    /**
     * Loads a media object from its path key.
     *
     * @param string $key the path key of the media to load
     *
     * @return bool true if the loading of this media was successful and
     *              false if the media with the given key doesn't exist
     */
    public function loadByPathKey($key)
    {
        $this->checkDB();

        $row = null;

        if ($this->table !== null) {
            $sql = sprintf(
                'select * from %s where path_key = %s',
                $this->table,
                $this->db->quote($key)
            );

            $rs = SwatDB::query($this->db, $sql, null);
            $row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
        }

        if ($row === null) {
            return false;
        }

        $this->initFromRow($row);
        $this->generatePropertyHashes();

        return true;
    }

    protected function loadEncodingBindings()
    {
        $sql = sprintf(
            'select * from MediaEncodingBinding
			where MediaEncodingBinding.media = %s %s',
            $this->db->quote($this->id, 'integer'),
            $this->getMediaEncodingBindingsOrderBy()
        );

        return SwatDB::query(
            $this->db,
            $sql,
            $this->getMediaEncodingBindingWrapperClass()
        );
    }

    protected function getMediaEncodingBindingWrapperClass()
    {
        return SwatDBClassMap::get(SiteMediaEncodingBindingWrapper::class);
    }

    protected function getMediaEncodingBindingsOrderBy()
    {
        return '';
    }

    protected function getSerializableSubDataObjects()
    {
        return ['media_set', 'encoding_bindings'];
    }

    protected function getSerializablePrivateProperties()
    {
        return array_merge(parent::getSerializablePrivateProperties(), ['media_set_shortname']);
    }

    // Processing methods

    /**
     * Deletes this object from the database and any media files
     * corresponding to this object that are local or on the CDN.
     */
    protected function deleteInternal()
    {
        $this->deleteCdnFiles();

        $local_files = $this->getLocalFilenamesToDelete();

        parent::deleteInternal();

        $this->deleteLocalFiles($local_files);
    }

    protected function deleteCdnFiles()
    {
        foreach ($this->media_set->encodings as $encoding) {
            $binding = $this->getEncodingBinding($encoding->shortname);

            if ($binding instanceof SiteMediaEncodingBinding
                && $binding->on_cdn == true) {
                $this->queueCdnTask('delete', $encoding);
            }
        }
    }

    /**
     * Gets an array of files names to delete when deleting this object.
     *
     * @return array an array of filenames
     */
    protected function getLocalFilenamesToDelete()
    {
        $filenames = [];

        foreach ($this->media_set->encodings as $encoding) {
            $binding = $this->getEncodingBinding($encoding->shortname);

            if ($binding instanceof SiteMediaEncodingBinding) {
                $filenames[] = $this->getFilePath($encoding->shortname);
            }
        }

        return $filenames;
    }

    /**
     * Deletes each file in a given set of filenames.
     *
     * @param array $filenames an array of filenames to delete
     */
    protected function deleteLocalFiles(array $filenames)
    {
        foreach ($filenames as $filename) {
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
    }

    /**
     * Queues a CDN task to be preformed later.
     *
     * @param string            $operation the operation to preform
     * @param SiteMediaEncoding $encoding  the media encoding we're queuing the
     *                                     action for
     */
    protected function queueCdnTask(
        $operation,
        SiteMediaEncoding $encoding
    ) {
        $this->checkDB();

        $class_name = SwatDBClassMap::get(SiteMediaCdnTask::class);
        $task = new $class_name();
        $task->setDatabase($this->db);
        $task->operation = $operation;

        if (($operation == 'copy') || ($operation == 'update')) {
            $task->media = $this;
            $task->encoding = $encoding;
            $task->override_http_headers = serialize(
                ['Content-Disposition' => sprintf(
                    'attachment; filename="%s"',
                    $this->getContentDispositionFilename(
                        $encoding->shortname
                    )
                )]
            );
        } else {
            $task->file_path = $this->getUriSuffix($encoding->shortname);
        }

        $task->save();
    }

    protected function getMediaSet()
    {
        if ($this->media_set instanceof SiteMediaSet) {
            return $this->media_set;
        }

        if ($this->media_set_shortname == '') {
            throw new SiteException('To process media, a media set ' .
                'shortname must be defined in the media dataobject.');
        }

        $class_name = SwatDBClassMap::get(SiteMediaSet::class);
        $media_set = new $class_name();
        $media_set->setDatabase($this->db);

        if ($media_set->loadByShortname($this->media_set_shortname) === false) {
            throw new SiteException(sprintf(
                'Media set “%s” does not exist.',
                $this->media_set_shortname
            ));
        }

        $this->media_set = $media_set;

        return $this->media_set;
    }

    // File and URI methods

    public function getUri($shortname, $prefix = '')
    {
        $uri = $this->getUriSuffix($shortname);

        // Don't apply the prefix if the media exists on a CDN since the media
        // will always be in the same location. We don't need to apply ../ for
        // media displayed in the admin.
        $binding = $this->getEncodingBinding($shortname);
        if ($binding->on_cdn && self::$cdn_base != '') {
            $uri = self::$cdn_base . $uri;
        } elseif ($prefix != '' && !mb_strpos($uri, '://')) {
            $uri = $prefix . $uri;
        }

        return $uri;
    }

    public function getUriSuffix($encoding_shortname)
    {
        $suffix = sprintf(
            '%s/%s/%s',
            $this->media_set->shortname,
            $encoding_shortname,
            $this->getFilename($encoding_shortname)
        );

        if ($this->getUriBase() != '') {
            $suffix = $this->getUriBase() . '/' . $suffix;
        }

        return $suffix;
    }

    public function setFileBase($file_base)
    {
        $this->file_base = $file_base;
    }

    public function getFileDirectory($encoding_shortname)
    {
        $items = [$this->getFileBase(), $this->media_set->shortname, $encoding_shortname];

        return implode(DIRECTORY_SEPARATOR, $items);
    }

    public function getFilePath($encoding_shortname)
    {
        $items = [$this->getFileDirectory($encoding_shortname), $this->getFilename($encoding_shortname)];

        return implode(DIRECTORY_SEPARATOR, $items);
    }

    public function getFilename($encoding_shortname)
    {
        $binding = $this->getEncodingBinding($encoding_shortname);

        if ($this->getMediaSet()->obfuscate_filename) {
            $filename = $this->filename;
        } else {
            $filename = $this->path_key;
        }

        return sprintf('%s.%s', $filename, $binding->media_type->extension);
    }

    public function getContentDispositionFilename($encoding_shortname)
    {
        $filename = $this->getFilename($encoding_shortname);

        // Convert to an ASCII string. Approximate non ACSII characters.
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);

        // Format the filename according to the qtext syntax in RFC 822
        return str_replace(
            ['\\', "\r", '"'],
            ['\\\\', "\\\r", '\"'],
            $filename
        );
    }

    public function getHttpHeaders($encoding_shortname)
    {
        $headers = [];

        // Set a "never-expire" policy with a far future max age (10 years)
        // as suggested in
        // http://developer.yahoo.com/performance/rules.html#expires.
        // As well, set Cache-Control to public, as this allows some
        // browsers to cache the media to disk while on https, which is a
        // good win. This depends on setting new object ids when updating
        // the object, if this isn't true of a subclass this will have to
        // be overwritten. Also note that Amazon CloudFront depends on this
        // value to decide how long the media sits on an edge location before
        // its invalidated. Long caching on the edge location is desirable, even
        // for private media.
        $headers['Cache-Control'] = 'public, max-age=315360000';

        $binding = $this->getEncodingBinding($encoding_shortname);

        $headers['Content-Type'] = $binding->media_type->mime_type;
        $headers['Content-Length'] = $binding->filesize;
        $headers['Content-Disposition'] = sprintf(
            'attachment; filename="%s"',
            $this->getContentDispositionFilename($encoding_shortname)
        );

        return $headers;
    }

    protected function getUriBase()
    {
        return 'media';
    }

    protected function getFileBase()
    {
        if ($this->file_base === null) {
            throw new SiteException('File base has not been set.');
        }

        return $this->file_base;
    }
}
