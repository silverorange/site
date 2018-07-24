<?php

/**
 * A recordset wrapper class for SitePromotionCode objects
 *
 * @package   Academy
 * @copyright 2018 silverorange
 * @see       SitePromotionCodeWrapper
 */
class SitePromotionCodeWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('SitePromotionCode');

		$this->index_field = 'id';
	}

	// }}}
}

?>
