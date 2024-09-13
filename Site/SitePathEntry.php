<?php

/**
 * A single entry in a {@link SitePath}.
 *
 * After a path entry is created, its properties are readable but not writeable.
 * Path entries have the following readable properties:
 * - id
 * - parent
 * - shortname
 * - title
 *
 * @copyright 2004-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SitePath
 */
class SitePathEntry
{
    /**
     * The database id of this entry.
     *
     * @var int
     */
    public $id;

    /**
     * The database id of the parent of this entry or null if this entry
     * does not have a parent.
     *
     * @var int
     */
    public $parent;

    /**
     * The shortname of this entry.
     *
     * @var string
     */
    public $shortname;

    /**
     * The title of this entry.
     *
     * @var string
     */
    public $title;

    /**
     * Creates a new path entry.
     *
     * @param int    $id        the database id of this entry
     * @param int    $parent    the database id of the parent of this entry or
     *                          null if this entry does not have a parent
     * @param string $shortname the shortname of this entry
     * @param string $title     the title of this entry
     */
    public function __construct($id, $parent, $shortname, $title)
    {
        $this->id = $id;
        $this->parent = $parent;
        $this->shortname = $shortname;
        $this->title = $title;
    }
}
