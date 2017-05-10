<?php


/**
 * A recordset wrapper class for SiteMedia objects
 *
 * Note: This recordset automatically loads media encoding bindings for
 *       media when constructed from a database result. If this behaviour is
 *       undesirable, set the lazy_load option to true.
 *
 * @package   Site
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteMedia
 */
class SiteMediaWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function initializeFromResultSet()

	public function initializeFromResultSet(MDB2_Result_Common $rs)
	{
		parent::initializeFromResultSet($rs);

		// automatically load media_set and encodings unless lazy_load is set
		// to true
		if (!$this->getOption('lazy_load')) {
			$this->loadAllSubDataObjects(
				'media_set',
				$this->db,
				'select * from MediaSet where id in (%s)',
				$this->getMediaSetWrapperClass()
			);

			$this->loadEncodingBindings();
		}
	}

	// }}}
	// {{{ public function loadEncodingBindings()

	public function loadEncodingBindings()
	{
		if ($this->getCount() > 0) {
			$ids = array();
			foreach ($this->getArray() as $media) {
				$ids[] = $this->db->quote($media->id, 'integer');
			}

			$sql = sprintf('select * from MediaEncodingBinding
				where MediaEncodingBinding.media in (%s)
				order by %s',
				implode(',', $ids),
				$this->getMediaEncodingBindingOrderBy());

			$wrapper_class = $this->getMediaEncodingBindingWrapperClass();

			$bindings = SwatDB::query($this->db, $sql,
				$wrapper_class);

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
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('SiteMedia');

		$this->index_field = 'id';
	}

	// }}}
	// {{{ protected function getMediaSetWrapperClass()

	protected function getMediaSetWrapperClass()
	{
		return SwatDBClassMap::get('SiteMediaSetWrapper');
	}

	// }}}
	// {{{ protected function getMediaEncodingBindingWrapperClass()

	protected function getMediaEncodingBindingWrapperClass()
	{
		return SwatDBClassMap::get('SiteMediaEncodingBindingWrapper');
	}

	// }}}
	// {{{ protected function getMediaEncodingBindingOrderBy()

	protected function getMediaEncodingBindingOrderBy()
	{
		return 'media';
	}

	// }}}
}

?>
