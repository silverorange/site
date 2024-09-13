<?php

/**
 * @copyright 2011-2016 silverorange
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
