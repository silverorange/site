<?php

/**
 * @copyright 2011-2016 silverorange
 *
 * @property int         $id
 * @property SwatDate    $login_date
 * @property ?string     $ip_address
 * @property ?string     $user_agent
 * @property SiteAccount $account
 */
class SiteAccountLoginHistory extends SwatDBDataObject
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
    public $login_date;

    /**
     * @var string
     */
    public $ip_address;

    /**
     * @var string
     */
    public $user_agent;

    protected function init()
    {
        $this->table = 'AccountLoginHistory';
        $this->id_field = 'integer:id';

        $this->registerDateProperty('login_date');

        $this->registerInternalProperty(
            'account',
            SwatDBClassMap::get(SiteAccount::class)
        );
    }
}
