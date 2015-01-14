<?php

require_once 'Site/dataobjects/SiteCdnTaskWrapper.php';
require_once 'Site/dataobjects/SiteMediaCdnTask.php';
require_once 'Site/dataobjects/SiteMediaWrapper.php';
require_once 'Site/dataobjects/SiteMediaEncodingWrapper.php';

/**
 * A recordset wrapper class for SiteMediaCdnTask objects
 *
 * Note: This recordset automatically loads media and encodings for tasks when
 *       constructed from a database result. If this behaviour is undesirable,
 *       set the lazy_load option to true.
 *
 * @package   Site
 * @copyright 2011-2015 silverorange
 * @see       SiteAttachmentCdnTask
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaCdnTaskWrapper extends SiteCdnTaskWrapper
{
	// {{{ public function initializeFromResultSet()

	public function initializeFromResultSet(MDB2_Result_Common $rs)
	{
		parent::initializeFromResultSet($rs);

		if (!$this->getOption('lazy_load')) {
			$this->loadAllSubDataObjects(
				'media',
				$this->db,
				'select * from Media where id in (%s)',
				SwatDBClassMap::get('SiteMediaWrapper')
			);

			$this->loadAllSubDataObjects(
				'encoding',
				$this->db,
				'select * from MediaEncoding where id in (%s)',
				SwatDBClassMap::get('SiteMediaEncodingWrapper')
			);
		}
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('SiteMediaCdnTask');
	}

	// }}}
}

?>
