<?php

/**
 * A media encoding binding object.
 *
 * This represents a physical encoding of a media resource. For example, a
 * single media object may have an encoding for both WebM and H.264.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property ?float        $filesize
 * @property bool          $on_cdn
 * @property int           $media_encoding
 * @property int           $media
 * @property SiteMediaType $media_type
 */
class SiteMediaEncodingBinding extends SwatDBDataObject
{
    /**
     * File size in bytes.
     *
     * Stored as float to prevent integer overflow.
     *
     * @var float
     */
    public $filesize;

    /**
     * Whether or not this encoding has been copied to the CDN.
     *
     * @var bool
     */
    public $on_cdn = false;

    /**
     * Encoding Id.
     *
     * This is not an internal property since alternative effiecient methods
     * are used to load encodings and encoding bindings.
     *
     * @var int
     */
    public $media_encoding;

    /**
     * Media Id.
     *
     * This is not an internal property since alternative effiecient methods
     * are used to load encodings and encoding bindings.
     *
     * @var int
     */
    public $media;

    private static $media_type_cache = [];

    /**
     * @throws SiteException
     */
    public function getHumanFileType(): string
    {
        return match ($this->media_type->mime_type) {
            'video/mp4'  => Site::_('MP4'),
            'audio/mp4'  => Site::_('M4A'),
            'audio/mpeg' => Site::_('MP3'),
            default      => throw new SiteException(
                sprintf('Unknown mime type %s', $this->media_type->mime_type)
            ),
        };
    }

    public function getFormattedFileSize()
    {
        return SwatString::byteFormat($this->filesize, -1, false, 1);
    }

    protected function init()
    {
        $this->table = 'MediaEncodingBinding';

        $this->registerInternalProperty(
            'media_type',
            SwatDBClassMap::get(SiteMediaType::class)
        );
    }

    protected function hasSubDataObject($key)
    {
        $found = parent::hasSubDataObject($key);

        if ($key === 'media_type' && !$found) {
            $media_type_id = $this->getInternalValue('media_type');

            if ($media_type_id !== null
                && array_key_exists($media_type_id, self::$media_type_cache)) {
                $this->setSubDataObject(
                    'media_type',
                    self::$media_type_cache[$media_type_id]
                );

                $found = true;
            }
        }

        return $found;
    }

    protected function setSubDataObject($name, $value)
    {
        if ($name === 'media_type') {
            self::$media_type_cache[$value->id] = $value;
        }

        parent::setSubDataObject($name, $value);
    }

    /**
     * Saves this object to the database.
     *
     * Only modified properties are updated.
     */
    protected function saveInternal()
    {
        $sql = sprintf(
            'delete from %s where media_encoding = %s and media = %s',
            $this->table,
            $this->db->quote($this->media_encoding, 'integer'),
            $this->db->quote($this->media, 'integer')
        );

        SwatDB::exec($this->db, $sql);

        parent::saveInternal();
    }

    protected function getSerializablePrivateProperties()
    {
        return array_merge(parent::getSerializablePrivateProperties(), ['media_type']);
    }
}
