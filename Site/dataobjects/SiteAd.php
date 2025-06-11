<?php

/**
 * Advertisements that are tracked on an applcation.
 *
 * This class is for tracking advertisements, not for displaying
 * advertisements.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SiteAdReferrer
 *
 * @property int       $id
 * @property ?string   $shortname
 * @property ?string   $title
 * @property ?SwatDate $createdate
 * @property int       $displayorder
 * @property int       $total_referrers
 */
class SiteAd extends SwatDBDataObject
{
    /**
     * Unique identifier for this advertisement.
     *
     * @var int
     */
    public $id;

    /**
     * A short, textual identifier for this advertisement.
     *
     * @var string
     */
    public $shortname;

    /**
     * A title visible to users for describing this advertisement.
     *
     * @var string
     */
    public $title;

    /**
     * The date this advertisement was created.
     *
     * @var SwatDate
     */
    public $createdate;

    /**
     * Display order of this advertisement.
     *
     * @var int
     */
    public $displayorder;

    /**
     * Total referrals based on this advertisement.
     *
     * @var int
     */
    public $total_referrers;

    /**
     * Total emails sent with the ad tracker.
     */
    public int $emails_sent = 0;

    protected function init()
    {
        $this->table = 'Ad';
        $this->id_field = 'integer:id';
        $this->registerDateProperty('createdate');
    }

    /**
     * Loads an ad from the database using the ad shortname.
     *
     * @param string $shortname the shortname of the ad
     *
     * @return bool true if the loading was successful and false if it was
     *              not
     */
    public function loadFromShortname($shortname)
    {
        $this->checkDB();
        $row = null;

        if ($this->table !== null) {
            $sql = sprintf(
                'select * from %s where shortname = %s',
                $this->table,
                $this->db->quote($shortname, 'text')
            );

            $rs = SwatDB::query($this->db, $sql, null);
            $row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
        }

        if ($row === null) {
            return false;
        }

        $this->initFromRow($row);
        $this->generatePropertyHashes();

        return true;
    }
}
