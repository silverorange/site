<?php

/**
 * Active sessions for this account. Used for both persistent login via cookie,
 * and to track and display active sessions to the user.
 *
 * @copyright 2012-2016 silverorange
 *
 * @property int         $id
 * @property SwatDate    $createdate
 * @property SwatDate    $login_date
 * @property string      $ip_address
 * @property ?string     $user_agent
 * @property ?string     $tag
 * @property string      $session_id
 * @property SiteAccount $account
 */
class SiteAccountLoginSession extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * @var SwatDate
     */
    public $createdate;

    /**
     * @var SwatDate
     */
    public $login_date;

    /**
     * @var string
     */
    public $ip_address;

    /**
     * @var string
     */
    public $user_agent;

    /**
     * @var string
     */
    public $tag;

    /**
     * @var string
     */
    public $session_id;

    /**
     * Whether or not the account of this session needs to be reloaded.
     */
    public bool $dirty = false;

    /**
     * Flags this session as needing to be reloaded.
     */
    public function setDirty()
    {
        $this->checkDB();

        $sql = sprintf(
            'update AccountLoginSession set dirty = %s where id = %s',
            $this->db->quote(true, 'boolean'),
            $this->db->quote($this->id, 'integer')
        );

        SwatDB::exec($this->db, $sql);
    }

    /**
     * Flags this session as NOT needing to be reloaded.
     */
    public function setClean()
    {
        $this->checkDB();

        $sql = sprintf(
            'update AccountLoginSession set dirty = %s where id = %s',
            $this->db->quote(false, 'boolean'),
            $this->db->quote($this->id, 'integer')
        );

        SwatDB::exec($this->db, $sql);
    }

    protected function init()
    {
        $this->table = 'AccountLoginSession';
        $this->id_field = 'integer:id';

        $this->registerDateProperty('createdate');
        $this->registerDateProperty('login_date');

        $this->registerInternalProperty(
            'account',
            SwatDBClassMap::get(SiteAccount::class)
        );
    }
}
