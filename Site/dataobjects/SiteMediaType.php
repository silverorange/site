<?php

/**
 * A media type object.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaType extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Extension.
     *
     * @var string
     */
    public $extension;

    /**
     * Mime type.
     *
     * @var string
     */
    public $mime_type;

    /**
     * Alternate mime types.
     *
     * A comma-deliminated list of alternate valid mime types to the
     * default mime type.
     *
     * @var string
     */
    public $alternate_mime_types;

    /**
     * Loads a media type from the database with a mime-type.
     *
     * @param string $mime_type The mime-type of the media type
     *
     * @return bool true if a type was successfully loaded and false if
     *              no set was found with the specified mime-type
     */
    public function loadByMimeType($mime_type)
    {
        $this->checkDB();

        $found = false;

        $sql = 'select * from %s where lower(mime_type) = lower(%s)';

        $sql = sprintf(
            $sql,
            $this->table,
            $this->db->quote($mime_type, 'text')
        );

        $row = SwatDB::queryRow($this->db, $sql);

        if ($row !== null) {
            $this->initFromRow($row);
            $this->generatePropertyHashes();
            $found = true;
        }

        return $found;
    }

    public function getValidMimeTypes()
    {
        $mime_types = [$this->mime_type];
        foreach (explode(',', $this->alternate_mime_types) as $type) {
            $mime_types[] = trim($type);
        }

        return array_unique($mime_types);
    }

    protected function init()
    {
        $this->table = 'MediaType';
        $this->id_field = 'integer:id';
    }
}
