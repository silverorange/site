<?php


/**
 * A recordset wrapper class for SiteAttachmentCdnTask objects
 *
 * Note: This recordset automatically loads attachments for tasks when
 *       constructed from a database result. If this behaviour is undesirable,
 *       set the lazy_load option to true.
 *
 * @package   Site
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAttachmentCdnTask
 */
class SiteAttachmentCdnTaskWrapper extends SiteCdnTaskWrapper
{
	// {{{ public function initializeFromResultSet()

	public function initializeFromResultSet(MDB2_Result_Common $rs)
	{
		parent::initializeFromResultSet($rs);

		// automatically load attachments unless lazy_load is set to true
		if (!$this->getOption('lazy_load')) {
			$this->loadAllSubDataObjects(
				'attachment',
				$this->db,
				'select * from Attachment where id in (%s)',
				SwatDBClassMap::get('SiteAttachmentWrapper')
			);
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
