<?php

/**
 * Interaface for an object that can receive comments.
 *
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
interface SiteCommentable extends SiteCommentStatus, SwatDBRecordable
{
    /**
     * Adds a new comment to this object.
     *
     * @param SiteComment $comment the comment to add
     */
    public function addComment(SiteComment $comment);

    /**
     * Gets the title of this object.
     *
     * @return string the title of this object
     */
    public function getTitle();
}
