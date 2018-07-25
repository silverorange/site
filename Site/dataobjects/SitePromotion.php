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
  // {{{ public function loadByApiIdent()

  public function loadByApiIdent($ident, $type, SiteApiCredential $credential)
  {
    $this->checkDB();

    $row = null;

    if ($this->table !== null) {
      $sql = sprintf(
        'select * from PromotionCode
        inner join Promotion on Promotion.id = PromotionCode.promotion
        where PromotionCode.api_ident = %s and
          Promotion.api_credential = %s and
          Promotion.api_sign_on_type = %s
        order by PromotionCode.createdate desc',
        $this->db->quote($ident, 'text'),
        $this->db->quote($credential->id, 'integer'),
        $this->db->quote($type, 'text')
      );

      $wrapper = SwatDBClassMap::get('SitePromotionCodeWrapper');
      $codes = SwatDB::query($this->db, $sql, $wrapper);
      $code = $codes->getFirst();
    }

    if (!$code instanceof PromotionCode) {
      return false;
    }

    $this->code = $code;

    return $this->load(
      $code->getInternalValue('promotion'),
      $credential->instance
    );
  }

  // }}}
  // {{{ public function generateApiCode()

  public function generateApiCode(SiteApplication $app, $api_ident)
  {
    $promotion_code = $this->generateCode($app);
    $promotion_code->api_ident = $api_ident;
    $promotion_code->save();

    $this->code = $promotion_code;
  }

  // }}}
	// {{{ protected function generateCode()

	public function generateCode(SiteApplication $app)
	{
		$generator = new PromoPromotionCodeGenerator($app);
		// The generator always returns an array, so pop the value off it.
		$codes = $generator->getCodes($this, 1);
		$code = array_pop($codes);

		$class_name = SwatDBClassMap::get('SitePromotionCode');
		$promotion_code = new $class_name();
		$promotion_code->setDatabase($this->db);
		$promotion_code->promotion = $this;
		$promotion_code->code = $code;
		$promotion_code->limited_use = true;
		$promotion_code->createdate = new SwatDate();
		$promotion_code->createdate->toUTC();

		return $promotion_code;
	}

	// }}}
}

?>
