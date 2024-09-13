<?php

/**
 * Site comment status constants.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
interface SiteCommentStatus
{
    /**
     * New comments are allowed, and are automatically show on the site as long
     * as they are not detected as spam.
     */
    public const OPEN = 0;

    /**
     * New comments are allowed, but must be approved by an admin user before
     * being shown.
     */
    public const MODERATED = 1;

    /**
     * No new comments are allowed, but exisiting comments are shown.
     */
    public const LOCKED = 2;

    /**
     * No new comments are allowed, and existing comments are no longer shown.
     */
    public const CLOSED = 3;

    public function getCommentStatus();
}
