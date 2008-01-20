<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Site/dataobjects/SiteImage.php';
require_once 'Site/dataobjects/SiteImageDimensionBindingWrapper.php';

/**
 * A recordset wrapper class for SiteImage objects
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteImage
 */
class SiteImageWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function __construct()

	/**
	 * Creates a new recordset wrapper
	 *
	 * @param MDB2_Result $recordset optional. The MDB2 recordset to wrap.
	 */
	public function __construct($recordset = null)
	{
		parent::__construct($recordset);

		if ($this->getCount() > 0) {
			$image_ids = array();
			foreach ($this->getArray() as $image)
				$image_ids[] = $this->db->quote($image->id, 'integer');

			$sql = sprintf('select ImageDimensionBinding.*
				from ImageDimensionBinding
				where ImageDimensionBinding.image in (%s)
				order by image',
				implode(',', $image_ids));

			$wrapper_class =
				SwatDBClassMap::get('SiteImageDimensionBindingWrapper');
			$bindings = SwatDB::query($this->db, $sql, $wrapper_class);

			$last_image = null;
			foreach ($bindings as $binding) {
				if ($last_image === null ||
					$last_image->id !== $binding->image) {

					if ($last_image !== null) {
						$wrapper->reindex();
						$last_image->dimension_bindings = $wrapper;
					}

					$last_image = $this->getByIndex($binding->image);
					$wrapper = new SiteImageDimensionBindingWrapper();
				}

				$wrapper->add($binding);
			}

			$wrapper->reindex();
			$last_image->dimension_bindings = $wrapper;
		}
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('SiteImage');
		$this->index_field = 'id';
	}

	// }}}
}

?>
