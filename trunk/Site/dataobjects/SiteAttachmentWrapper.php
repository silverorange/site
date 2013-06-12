<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteAttachment.php';

/**
 * A recordset wrapper class for SiteAttachment objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAttachment
 */
class SiteAttachmentWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('SiteAttachment');
		$this->index_field = 'id';
	}

	// }}}
	// {{{ public function getBySet()

	public function getBySet($shortname)
	{
		$wrapper_class = SwatDBClassMap::get('SiteAttachmentWrapper');
		$attachments = new $wrapper_class();

		foreach ($this as $attachment) {
			if ($attachment->attachment_set->shortname === $shortname) {
				$attachments->add($attachment);
			}
		}

		return $attachments;
	}

	// }}}
}

?>
