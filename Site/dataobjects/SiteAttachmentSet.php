<?php

/**
 * An attachment set.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property int    $id
 * @property string $title
 * @property string $shortname
 * @property bool   $use_cdn
 * @property bool   $obfuscate_filename
 */
class SiteAttachmentSet extends SwatDBDataObject
{
    /**
     * The unique identifier of this type.
     *
     * @var int
     */
    public $id;

    /**
     * The title of this type.
     *
     * @var string
     */
    public $title;

    /**
     * The shortname of this type.
     *
     * @var string
     */
    public $shortname;

    /**
     * @var bool
     */
    public $use_cdn;

    /**
     * @var bool
     */
    public $obfuscate_filename;

    public function loadByShortname($shortname)
    {
        $this->checkDB();

        $found = false;

        $sql = 'select * from %s where shortname = %s';

        $sql = sprintf(
            $sql,
            $this->table,
            $this->db->quote($shortname, 'text')
        );

        $row = SwatDB::queryRow($this->db, $sql);

        if ($row !== null) {
            $this->initFromRow($row);
            $this->generatePropertyHashes();
            $found = true;
        }

        return $found;
    }

    protected function init()
    {
        $this->table = 'AttachmentSet';
        $this->id_field = 'integer:id';
    }
}
