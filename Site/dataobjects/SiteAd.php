<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * Advertisements that are tracked on an applcation
 *
 * This class is for tracking advertisements, not for displaying
 * advertisements.
 *
 * @package   Site
 * @copyright 2006-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAdReferrer
 */
class SiteAd extends SwatDBDataObject
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
	 * @var SwatDate
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
	public $total_referrers;

	/**
	 * Total emails sent with the ad tracker.
	 *
	 * @var integer
	 */
	public $emails_sent = 0;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Ad';
		$this->id_field = 'integer:id';
		$this->registerDateProperty('createdate');
	}

	// }}}
	// {{{ public function loadFromShortname()

	/**
	 * Loads an ad from the database using the ad shortname
	 *
	 * @param string $shortname the shortname of the ad.
	 *
	 * @return boolean true if the loading was successful and false if it was
	 *                  not.
	 */
	public function loadFromShortname($shortname)
	{
		$this->checkDB();
		$row = null;

		if ($this->table !== null) {
			$sql = sprintf('select * from %s where shortname = %s',
				$this->table,
				$this->db->quote($shortname, 'text'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row === null)
			return false;

		$this->initFromRow($row);
		$this->generatePropertyHashes($row);
		return true;
	}

	// }}}
}

?>
