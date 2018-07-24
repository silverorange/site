<?php

/**
 * @package   Academy
 * @copyright 2011-2016 silverorange
 */
class SitePromotion extends SwatDBDataObject
{
	// }}}
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Title of the promotion
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Start date of promotion
	 *
	 * @var SwatDate
	 */
	public $start_date;

	/**
	 * End date of promotion
	 *
	 * @var SwatDate
	 */
	public $end_date;

	/**
	 * A fixed amount discount
	 *
	 * @var float
	 */
	public $discount_amount;

	/**
	 * A percentage based discount
	 *
	 * @var float
	 */
	public $discount_percentage;

	/**
	 * Only visible in the admin
	 *
	 * @var string
	 */
	public $notes;

	/**
	 * Public note, shown to customers
	 *
	 * @var string
	 */
	public $public_note;

	/**
	 * Maximum quantity of items for purchase
	 *
	 * @var integer
	 */
	public $maximum_quantity;


	/**
	 * @var string
	 */
	public $api_sign_on_type;

	// }}}
}

?>
