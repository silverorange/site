<?php

require_once 'Site/dataobjects/SiteCdnTaskWrapper.php';
require_once 'Site/dataobjects/SiteMediaCdnTask.php';
require_once 'Site/dataobjects/SiteMediaWrapper.php';
require_once 'Site/dataobjects/SiteMediaEncodingWrapper.php';

/**
 * A recordset wrapper class for SiteMediaCdnTask objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @see       SiteAttachmentCdnTask
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaCdnTaskWrapper extends SiteCdnTaskWrapper
{
	// {{{ public function __construct()

	public function __construct($recordset = null)
	{
		parent::__construct($recordset);

		if ($recordset !== null) {
			$this->loadAllSubDataObjects(
				'media',
				$this->db,
				'select * from Media where id in (%s)',
				SwatDBClassMap::get('SiteMediaWrapper'));

			$this->loadAllSubDataObjects(
				'encoding',
				$this->db,
				'select * from MediaEncoding where id in (%s)',
				SwatDBClassMap::get('SiteMediaEncodingWrapper'));
		}
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('SiteMediaCdnTask');
	}

	// }}}
}

?>
