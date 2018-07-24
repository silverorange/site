<?php

/**
 * @package   Academy
 * @copyright 2018 silverorange
 */
class SitePromotionCode extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Code for lookup
	 *
	 * @var string
	 */
	public $code;

	/**
	 * Date the code was created
	 *
	 * @var SwtaDate
	 */
	public $createdate;

	/**
	 * Used date of this code (limited only)
	 *
	 * @var SwatDate
	 */
	public $used_date;

	/**
	 * Whether this code can only be used once
	 *
	 * @var boolean
	 */
	public $limited_use;

	/**
	 * The indentifier of the API user who generated this code
	 *
	 * @var string
	 */
	public $api_ident;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->table = 'PromotionCode';
		$this->id_field = 'integer:id';

		$this->registerDateProperty('createdate');
		$this->registerDateProperty('used_date');

		$this->registerInternalProperty(
			'promotion',
			SwatDBClassMap::get('SitePromotion')
		);

		$this->registerDeprecatedProperty('subscription_end_date');
	}

	// }}}
}

?>
