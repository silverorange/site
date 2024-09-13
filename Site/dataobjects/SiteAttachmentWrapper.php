<?php

/**
 * A recordset wrapper class for SiteAttachment objects
 *
 * @package   Site
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAttachment
 */
class SiteAttachmentWrapper extends SwatDBRecordsetWrapper
{


	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get(SiteAttachment::class);
		$this->index_field = 'id';
	}




	public function getBySet($shortname)
	{
		$wrapper_class = SwatDBClassMap::get(SiteAttachmentWrapper::class);
		$attachments = new $wrapper_class();

		foreach ($this as $attachment) {
			if ($attachment->attachment_set->shortname === $shortname) {
				$attachments->add($attachment);
			}
		}

		return $attachments;
	}


}

?>
