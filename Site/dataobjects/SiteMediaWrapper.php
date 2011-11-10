<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteMedia.php';
require_once 'Site/dataobjects/SiteMediaSetWrapper.php';
require_once 'Site/dataobjects/SiteMediaEncodingBindingWrapper.php';

/**
 * A recordset wrapper class for SiteMedia objects
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteMedia
 */
class SiteMediaWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function __construct()

	public function __construct($recordset = null)
	{
		parent::__construct($recordset);

		if ($recordset !== null) {
			$this->loadAllSubDataObjects(
				'media_set',
				$this->db,
				'select * from MediaSet where id in (%s)',
				SwatDBClassMap::get('SiteMediaSetWrapper'));
		}

		$this->attachEncodingBindings();
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = 'Media';
		$this->index_field = 'id';
	}

	// }}}
	// {{{ protected function attachEncodingBindings()

	protected function attachEncodingBindings()
	{
		if ($this->getCount() > 0) {
			$ids = array();
			foreach ($this->getArray() as $media) {
				$ids[] = $this->db->quote($media->id, 'integer');
			}

			$sql = sprintf('select * from MediaEncodingBinding
				where MediaEncodingBinding.media in (%s)
				order by media, width asc nulls first',
				implode(',', $ids));

			$wrapper_class = SwatDBClassMap::get(
				'SiteMediaEncodingBindingWrapper');

			$bindings = SwatDB::query($this->db, $sql, $wrapper_class);

			if (count($bindings) == 0) {
				return;
			}

			$last_media = null;
			foreach ($bindings as $binding) {
				if ($last_media === null ||
					$last_media->id !== $binding->media) {

					if ($last_media !== null) {
						$wrapper->reindex();
						$last_media->encoding_bindings = $wrapper;
					}

					$last_media = $this->getByIndex($binding->media);
					$wrapper = new $wrapper_class();
				}

				$wrapper->add($binding);
			}

			$wrapper->reindex();
			$last_media->encoding_bindings = $wrapper;
		}
	}

	// }}}
}

?>
