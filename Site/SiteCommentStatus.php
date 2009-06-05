<?php

/**
 * Site comment status constants
 *
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteCommentStatus
{
	// {{{ class constants

	/**
	 * New comments are allowed, and are automatically show on the site as long
	 * as they are not detected as spam.
	 */
	const OPEN      = 0;

	/**
	 * New comments are allowed, but must be approved by an admin user before
	 * being shown.
	 */
	const MODERATED = 1;

	/**
	 * No new comments are allowed, but exisiting comments are shown.
	 */
	const LOCKED    = 2;

	/**
	 * No new comments are allowed, and existing comments are no longer shown.
	 */
	const CLOSED    = 3;

	// }}}
}

?>
