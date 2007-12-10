<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * Advertisements that are tracked on an e-commerce web applcation
 *
 * This class is for tracking advertisements, not for displaying
 * advertisements.
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAdReferer
 */
class StoreAd extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier for this advertisement
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * A short, textual identifier for this advertisement
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * A title visible to users for describing this advertisement
	 *
	 * @var string
	 */
	public $title;

	/**
	 * The date this advertisement was created
	 *
	 * @var Date
	 */
	public $createdate;

	/**
	 * Display order of this advertisement
	 *
	 * @var integer
	 */
	public $displayorder;

	/**
	 * Total referrals based on this advertisement
	 *
	 * @var integer
	 */
	public $total_referers;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Ad';
		$this->id_field = 'integer:id';
		$this->registerDateProperty('createdate');
	}

	// }}}
}

?>
