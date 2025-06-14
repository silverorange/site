<?php

/**
 * A media set object.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property int                      $id
 * @property string                   $shortname
 * @property bool                     $obfuscate_filename
 * @property bool                     $use_cdn
 * @property bool                     $private
 * @property ?SiteInstance            $instance
 * @property SiteMediaEncodingWrapper $encodings
 */
class SiteMediaSet extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Short, textual identifer for this set.
     *
     * The shortname must be unique.
     *
     * @var string
     */
    public $shortname;

    /**
     * Obfuscate filename.
     *
     * @var bool
     */
    public $obfuscate_filename;

    /**
     * Whether or not images added to this media set should be saved to a CDN.
     *
     * @var bool
     */
    public $use_cdn;

    /**
     * Whether or not this media is private.
     *
     * @var bool
     */
    public $private;

    /**
     * Loads a set from the database with a shortname.
     *
     * @param string       $shortname the shortname of the set
     * @param SiteInstance $instance  optional instance
     *
     * @return bool true if a set was successfully loaded and false if
     *              no set was found at the specified shortname
     */
    public function loadByShortname($shortname, ?SiteInstance $instance = null)
    {
        $this->checkDB();

        $found = false;

        $sql = 'select * from %s where shortname = %s';

        $sql = sprintf(
            $sql,
            $this->table,
            $this->db->quote($shortname, 'text')
        );

        if ($instance instanceof SiteInstance) {
            $sql .= sprintf(
                ' and (instance is null or instance = %s)',
                $instance->id
            );
        }

        $row = SwatDB::queryRow($this->db, $sql);

        if ($row !== null) {
            $this->initFromRow($row);
            $this->generatePropertyHashes();
            $found = true;
        }

        return $found;
    }

    /**
     * Checks existance of an encoding by its shortname.
     *
     * @param string $shortname the shortname of the encoding
     *
     * @return bool whether the encoding with the given shortname exists
     */
    public function hasEncoding($shortname)
    {
        $found = false;

        foreach ($this->encodings as $encoding) {
            if ($encoding->shortname === $shortname) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * Gets an encoding of this set based on its shortname.
     *
     * @param string $shortname the shortname of the encoding
     *
     * @return SiteMediaEncoding the encoding with the given shortname
     */
    public function getEncodingByShortname($shortname)
    {
        foreach ($this->encodings as $encoding) {
            // don't do an explicit equal as encoding shortnames can be numeric,
            // for example the pixel width of the encoding.
            if ($encoding->shortname == $shortname) {
                return $encoding;
            }
        }

        throw new SiteException(sprintf(
            'Media encoding “%s” does not exist.',
            $shortname
        ));
    }

    /**
     * Gets the shortname of an encoding of this set based on its id.
     *
     * @param int $id the id of the encoding
     *
     * @return string the shortname of the encoding
     */
    public function getEncodingShortnameById($id)
    {
        foreach ($this->encodings as $encoding) {
            if ($encoding->id === $id) {
                return $encoding->shortname;
            }
        }

        throw new SiteException(sprintf(
            'Media encoding “%s” does not exist.',
            $id
        ));
    }

    protected function init()
    {
        $this->registerInternalProperty(
            'instance',
            SwatDBClassMap::get(SiteInstance::class)
        );

        $this->table = 'MediaSet';
        $this->id_field = 'integer:id';
    }

    protected function getSerializableSubdataobjects()
    {
        return ['encodings'];
    }

    // loader methods

    /**
     * Loads the encodings belonging to this set.
     *
     * @return SiteMediaEncodingWrapper a set of encoding data objects
     */
    protected function loadEncodings()
    {
        $sql = 'select * from MediaEncoding
			where media_set = %s
			order by %s';

        $sql = sprintf(
            $sql,
            $this->db->quote($this->id, 'integer'),
            $this->getMediaEncodingOrderBy()
        );

        return SwatDB::query(
            $this->db,
            $sql,
            $this->getMediaEncodingWrapperClass()
        );
    }

    protected function getMediaEncodingWrapperClass()
    {
        return SwatDBClassMap::get(SiteMediaEncodingWrapper::class);
    }

    protected function getMediaEncodingOrderBy()
    {
        return 'id';
    }
}
