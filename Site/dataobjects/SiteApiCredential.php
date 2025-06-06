<?php

/**
 * API credentials.
 *
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property int          $id
 * @property string       $title
 * @property string       $api_key
 * @property string       $api_shared_secret
 * @property SwatDate     $createdate
 * @property SiteInstance $siteInstance
 */
class SiteApiCredential extends SwatDBDataObject
{
    /**
     * The unique identifier of this credential.
     *
     * @var int
     */
    public $id;

    /**
     * The title of the owner of the credentials.
     *
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $api_key;

    /**
     * @var string
     */
    public $api_shared_secret;

    /**
     * The date that these credentials were created.
     *
     * @var SwatDate
     */
    public $createdate;

    public function loadByApiKey($key, ?SiteInstance $instance = null)
    {
        $this->checkDB();

        $row = null;

        if ($this->table !== null) {
            $sql = sprintf(
                'select * from %s where api_key = %s',
                $this->table,
                $this->db->quote($key, 'text')
            );

            if ($instance instanceof SiteInstance) {
                $sql = sprintf(
                    '%s and instance = %s',
                    $sql,
                    $this->db->quote($instance->id, 'integer')
                );
            }

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

    protected function init()
    {
        $this->table = 'ApiCredential';
        $this->id_field = 'integer:id';
        $this->registerDateProperty('createdate');

        $this->registerInternalProperty(
            'instance',
            SwatDBClassMap::get(SiteInstance::class)
        );
    }

    // saver methods

    protected function saveInternal()
    {
        if ($this->id === null) {
            $this->createdate = new SwatDate();
            $this->createdate->toUTC();
        }

        parent::saveInternal();
    }
}
