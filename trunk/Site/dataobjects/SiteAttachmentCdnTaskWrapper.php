<?php

require_once 'Site/dataobjects/SiteCdnTaskWrapper.php';
require_once 'Site/dataobjects/SiteAttachmentCdnTask.php';
require_once 'Site/dataobjects/SiteAttachmentWrapper.php';

/**
 * A recordset wrapper class for SiteAttachmentCdnTask objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAttachmentCdnTask
 */
class SiteAttachmentCdnTaskWrapper extends SiteCdnTaskWrapper
{
	// {{{ public function __construct()

	public function __construct($recordset = null)
	{
		parent::__construct($recordset);

		if ($recordset !== null) {
			$this->loadAllSubDataObjects(
				'attachment',
				$this->db,
				'select * from Attachment where id in (%s)',
				SwatDBClassMap::get('SiteAttachmentWrapper'));
		}
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('SiteAttachmentCdnTask');
	}

	// }}}
}

?>
