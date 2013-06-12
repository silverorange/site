<?php

require_once 'SwatDB/SwatDBRecordable.php';
require_once 'Site/SiteCommentStatus.php';
require_once 'Site/dataobjects/SiteComment.php';

/**
 * Interaface for an object that can receive comments
 *
 * @package   Site
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
interface SiteCommentable extends SiteCommentStatus, SwatDBRecordable
{
	// {{{ public function addComment()

	/**
	 * Adds a new comment to this object
	 *
	 * @param SiteComment $comment the comment to add.
	 */
	public function addComment(SiteComment $comment);

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this object
	 *
	 * @return string the title of this object.
	 */
	public function getTitle();

	// }}}
}

?>
