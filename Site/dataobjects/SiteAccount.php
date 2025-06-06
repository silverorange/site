<?php

/**
 * A account for a web application.
 *
 * SiteAccount objects contain data like name and email that correspond
 * directly to database fields.
 *
 * There are three typical ways to use a SiteAccount object:
 *
 * - Create a new SiteAccount object with a blank constructor. Modify some
 *   properties of the account object and call the SiteAccount::save()
 *   method. A new row is inserted into the database.
 *
 * <code>
 * $crypt = $this->app->getModule('SiteCryptModule');
 *
 * $new_account = new SiteAccount();
 * $new_account->setEmail('account@example.com');
 * $new_account->setPasswordHash($crypt->generateHash('secretpassword'));
 * $new_account->save();
 * </code>
 *
 * - Create a new SiteAccount object with a blank constructor. Call the
 *   {@link SiteAccount::load()} or {@link SiteAccount::loadWithEmail}
 *   method on the object instance. Modify some properties and call the save()
 *   method. The modified properties are updated in the database.
 *
 * <code>
 * // using regular data-object load() method
 * $account = new SiteAccount();
 * $account->load(123);
 * echo 'Hello ' . $account->getFullName();
 * $account->setEmail('new_address@example.com');
 * $account->save();
 *
 * // using loadWithEmail()
 * $crypt = $this->app->getModule('SiteCryptModule');
 *
 * $account = new SiteAccount();
 * if ($account->loadWithEmail('test@example.com')) {
 *     $password_hash = $account->password;
 *     $password_salt = $account->password_salt;
 *
 *     if($crypt->verifyHash('secretpassword', $password_hash, $password_salt)) {
 *         echo 'Hello ' . $account->getFullName();
 *         $account->setEmail('new_address@example.com');
 *         $account->save();
 *     }
 * }
 * </code>
 *
 * - Create a new SiteAccount object passing a record set into the
 *   constructor. The first row of the record set will be loaded as the data
 *   for the object instance. Modify some properties and call the
 *   SiteAccount::save() method. The modified properties are updated
 *   in the database.
 *
 * Example usage as an MDB2 wrapper:
 *
 * <code>
 * $sql = '-- select an account here';
 * $account = $db->query($sql, null, true, 'Account');
 * echo 'Hello ' . $account->getFullName();
 * $account->setEmail('new_address@example.com');
 * $account->save();
 * </code>
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SiteAccountWrapper
 *
 * @phpstan-type TSuspiciousActivity object{
 *     account: int,
 *     first_suspicious_login: SwatDate,
 *     last_suspicious_login: SwatDate,
 *     login_count: int,
 *     user_agent_count: int,
 *     ip_address_count: int
 * }
 *
 * @property int                            $id
 * @property string                         $password_salt
 * @property string                         $password_tag
 * @property SwatDate                       $createdate
 * @property SwatDate                       $last_login
 * @property SwatDate                       $delete_date
 * @property string                         $unencrypted_password
 * @property string                         $fullname
 * @property string                         $email
 * @property string                         $password
 * @property ?TSuspiciousActivity           $suspicious_activity
 * @property SiteInstance                   $instance
 * @property SiteAccountLoginHistoryWrapper $login_history
 * @property SiteAccountLoginSessionWrapper $login_sessions
 */
class SiteAccount extends SwatDBDataObject
{
    /**
     * The database id of this account.
     *
     * If this property is null or 0 when SiteAccount::save() method is
     * called, a new account is inserted in the database.
     *
     * @var string
     *
     * @todo Should be int to match DB field
     */
    public $id;

    /**
     * The salt value used to protect this account's password base-64 encoded.
     *
     * @var string
     */
    public $password_salt;

    /**
     * Hashed password tag for reseting the account password.
     *
     * @var string
     */
    public $password_tag;

    /**
     * The date this account was created on.
     *
     * @var SwatDate
     */
    public $createdate;

    /**
     * The last date on which this account was logged into.
     *
     * @var SwatDate
     */
    public $last_login;

    /**
     * Date the account was deleted from the site.
     *
     * If delete date is set on an account, we consider it the same as having
     * the rows removed from the database.
     *
     * @var SwatDate
     */
    public $delete_date;

    /**
     * The password for this account.
     *
     * Note: This should NEVER be saved. Not ever.
     *
     * @var string
     */
    protected $unencrypted_password;

    /**
     * @see SiteAccount::getSuspiciousActivity()
     */
    protected $suspicious_activity;

    /**
     * The full name of this account.
     *
     * @var string
     */
    protected $fullname;

    /**
     * The email address of this account.
     *
     * @var string
     */
    protected $email;

    /**
     * Hashed version of this account's salted password.
     *
     * By design, there is no way to get the actual password of this account
     * through the SiteAccount object.
     *
     * @var string
     *
     * @see SiteAccount::setPasswordHash()
     */
    protected $password;

    /**
     * Loads an account from the database with just an email address.
     *
     * This is useful for password recovery and email address verification.
     *
     * @param string       $email    the email address of the account
     * @param SiteInstance $instance Optional site instance for this account.
     *                               Used when the site implements
     *                               {@link SiteMultipleInstanceModule}.
     *
     * @return bool true if the loading was successful and false if it was
     *              not
     */
    public function loadWithEmail($email, ?SiteInstance $instance = null)
    {
        $this->checkDB();

        // delete_date is checked in this query so we select the correct
        // account id before passing it to Account::load();
        $sql = sprintf(
            'select id from %s
			where lower(email) = lower(%s) and delete_date %s %s',
            $this->table,
            $this->db->quote($email, 'text'),
            SwatDB::equalityOperator(null),
            $this->db->quote(null, 'date')
        );

        if ($instance !== null) {
            $sql .= sprintf(
                ' and instance = %s',
                $this->db->quote($instance->id, 'integer')
            );
        }

        $id = SwatDB::queryOne($this->db, $sql);

        if ($id === null) {
            return false;
        }

        return $this->load($id);
    }

    /**
     * Loads this account from the database by one of its login tags.
     *
     * @param string       $login_tag the login tag to use
     * @param SiteInstance $instance  Optional site instance for this account.
     *                                Used when the site implements
     *                                {@link SiteMultipleInstanceModule}.
     *
     * @return bool true if the loading was successful and false if it was
     *              not
     */
    public function loadByLoginTag($login_tag, ?SiteInstance $instance = null)
    {
        $this->checkDB();

        // delete_date is checked in this query so we select the correct
        // account id before passing it to Account::load();
        $sql = sprintf(
            'select account from AccountLoginSession
				inner join Account on Account.id = AccountLoginSession.account
			where tag = %s and delete_date %s %s',
            $this->db->quote($login_tag, 'text'),
            SwatDB::equalityOperator(null),
            $this->db->quote(null, 'date')
        );

        if ($instance !== null) {
            $sql .= sprintf(
                ' and Account.instance = %s',
                $this->db->quote($instance->id, 'integer')
            );
        }

        $id = SwatDB::queryOne($this->db, $sql);

        if ($id === null) {
            return false;
        }

        return $this->load($id);
    }

    /**
     * Loads this account from the database by it's reset password tag.
     *
     * @param string       $password_tag the password tag to use
     * @param SiteInstance $instance     Optional site instance for this account.
     *                                   Used when the site implements
     *                                   {@link SiteMultipleInstanceModule}.
     *
     * @return bool true if the loading was successful and false if it was
     *              not
     */
    public function loadByPasswordTag(
        $password_tag,
        ?SiteInstance $instance = null
    ) {
        $this->checkDB();

        // delete_date is checked in this query so we select the correct
        // account id before passing it to Account::load();
        $sql = sprintf(
            'select id from Account
			where password_tag = %s and delete_date %s %s',
            $this->db->quote($password_tag, 'text'),
            SwatDB::equalityOperator(null),
            $this->db->quote(null, 'date')
        );

        if ($instance !== null) {
            $sql .= sprintf(
                ' and Account.instance = %s',
                $this->db->quote($instance->id, 'integer')
            );
        }

        $id = SwatDB::queryOne($this->db, $sql);

        if ($id === null) {
            return false;
        }

        return $this->load($id);
    }

    /**
     * Sets the last login date of this account in the database to the
     * specified date and also records the login in the
     * AccountLoginHistory table.
     *
     * @param SwatDate $date      the last login date of this account
     * @param string   $remote_ip the remote IP of the user logging in
     */
    public function updateLastLoginDate(SwatDate $date, $remote_ip = null)
    {
        $this->checkDB();

        // old way: update last_login date field
        $id_field = new SwatDBField($this->id_field, 'integer');
        $sql = sprintf(
            'update %s set last_login = %s where %s = %s',
            $this->table,
            $this->db->quote($date->getDate(), 'date'),
            $id_field->name,
            $this->db->quote($this->getId(), $id_field->type)
        );

        SwatDB::exec($this->db, $sql);

        // set date on current object and update property hash so the
        // date property is not modified (we just saved it)
        $this->last_login = $date;
        $this->generatePropertyHash('last_login');

        // new way: save to history table
        $history = $this->getNewLoginHistory($date, $remote_ip);
        $history->save();
    }

    public function getSuspiciousActivity()
    {
        if ($this->suspicious_activity === null) {
            $this->checkDB();

            $sql = sprintf(
                'select * from SuspiciousAccountView
				where account = %s',
                $this->db->quote($this->id, 'integer')
            );

            $this->suspicious_activity =
                SwatDB::query($this->db, $sql)->getFirst();
        }

        return $this->suspicious_activity;
    }

    /**
     * Flags this account as needing to be reloaded in all sessions.
     */
    public function setDirty()
    {
        $this->checkDB();

        $sql = sprintf(
            'update AccountLoginSession set dirty = %s where account = %s',
            $this->db->quote(true, 'boolean'),
            $this->db->quote($this->id, 'integer')
        );

        SwatDB::exec($this->db, $sql);
    }

    public static function getSuspiciousActivitySummary($activity)
    {
        $locale = SwatI18NLocale::get();

        return sprintf(
            Site::_('In the last week: %s logins from %s IPs' .
            ' and %s different browsers.'),
            $locale->formatNumber($activity->login_count),
            $locale->formatNumber($activity->ip_address_count),
            $locale->formatNumber($activity->user_agent_count)
        );
    }

    protected function loadInternal($id)
    {
        if ($this->table !== null && $this->id_field !== null) {
            $id_field = new SwatDBField($this->id_field, 'integer');
            $sql = 'select * from %s where %s = %s and delete_date %s %s';

            $sql = sprintf(
                $sql,
                $this->table,
                $id_field->name,
                $this->db->quote($id, $id_field->type),
                SwatDB::equalityOperator(null),
                $this->db->quote(null, 'date')
            );

            $rs = SwatDB::query($this->db, $sql, null);

            return $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
        }

        return null;
    }

    protected function deleteInternal()
    {
        if ($this->table === null || $this->id_field === null) {
            return;
        }

        $id_field = new SwatDBField($this->id_field, 'integer');

        if (!property_exists($this, $id_field->name)) {
            return;
        }

        $id_ref = $id_field->name;
        $id = $this->{$id_ref};

        $now = new SwatDate();
        $now->toUTC();

        $sql = sprintf(
            'update %s set delete_date = %s where %s = %s',
            $this->table,
            $this->db->quote($now, 'date'),
            $id_ref,
            $id
        );

        SwatDB::exec($this->db, $sql);
    }

    protected function init()
    {
        $this->table = 'Account';
        $this->id_field = 'integer:id';

        $this->registerDateProperty('createdate');
        $this->registerDateProperty('last_login');
        $this->registerDateProperty('delete_date');

        $this->registerInternalProperty(
            'instance',
            SwatDBClassMap::get(SiteInstance::class)
        );
    }

    protected function getNewLoginHistory(SwatDate $date, $remote_ip = null)
    {
        $class = SwatDBClassMap::get(SiteAccountLoginHistory::class);

        $history = new $class();
        $history->setDatabase($this->db);

        $history->account = $this->id;
        $history->login_date = $date;
        $history->ip_address = $remote_ip;

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];

            // Filter bad character encoding. If invalid, assume ISO-8859-1
            // encoding and convert to UTF-8.
            if (!SwatString::validateUtf8($user_agent)) {
                $user_agent = iconv('ISO-8859-1', 'UTF-8', $user_agent);
            }

            // Only save if the user-agent was successfully converted to UTF-8.
            if ($user_agent !== false) {
                // set max length based on database field length
                $user_agent = mb_substr($user_agent, 0, 255);
                $history->user_agent = $user_agent;
            }
        }

        return $history;
    }

    protected function getProtectedPropertyList()
    {
        $properties = parent::getProtectedPropertyList();

        $properties['fullname'] = ['get' => 'getFullName', 'set' => 'setFullName'];

        $properties['email'] = ['get' => 'getEmail', 'set' => 'setEmail'];

        $properties['password'] = ['get' => 'getPassword', 'set' => 'setPassword'];

        return $properties;
    }

    // getters

    /**
     * Gets the full name of the person who owns this account.
     *
     * Having this method allows subclasses to split the full name into an
     * arbitrary number of fields. For example, first name and last name.
     *
     * @return string the full name of the person who owns this account
     */
    public function getFullName()
    {
        return $this->fullname;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword()
    {
        return $this->password;
    }

    // setters

    public function setFullName($fullname)
    {
        $this->fullname = $fullname;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    // password methods

    /**
     * Sets this account's password hash.
     *
     * @param string $password_hash the password hash for this account
     */
    public function setPasswordHash($password_hash)
    {
        $this->setPassword($password_hash);

        // Note: Site now uses crypt() for password hashing. The salt is stored
        // in the same field as the hashed password.
        $this->password_salt = null;
    }

    // reset password methods

    /**
     * Creates a unique tag that can be used to reset this account's password.
     *
     * @param SiteApplication $app the application generating the new password
     */
    public function resetPassword(SiteApplication $app)
    {
        $this->checkDB();

        $id_field = new SwatDBField($this->id_field, 'integer');

        $fields = $this->getResetPasswordFields();
        $values = $this->getResetPasswordValues();

        // Update the database with new password tag. Don't use the regular
        // dataobject saving here in case other fields have changed.
        $transaction = new SwatDBTransaction($this->db);

        try {
            SwatDB::updateRow(
                $this->db,
                $this->table,
                $fields,
                $values,
                $id_field,
                $this->{$id_field->name}
            );

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollback();

            throw $e;
        }

        // Update the object's properties with the new values
        $this->updateResetPasswordValues($values);
    }

    /**
     * Emails this account's holder with instructions on how to finish
     * resetting his or her password.
     *
     * @param SiteApplication $app the application sending mail
     *
     * @see StoreAccount::resetPassword()
     */
    public function sendResetPasswordMailMessage(SiteApplication $app)
    {
        if ($this->password_tag == '') {
            throw new SiteException(Site::_(
                'This account’s password tag has not been set. ' .
                'Before a reset password mail message can be sent ' .
                'resetPassword() must be called.'
            ));
        }

        $title = $app->config->site->title;
        $link = $this->getResetPasswordUri($app);

        $email = new SiteResetPasswordMailMessage(
            $app,
            $this,
            $link,
            $title
        );

        $email->smtp_server = $app->config->email->smtp_server;
        $email->smtp_port = $app->config->email->smtp_port;
        $email->smtp_username = $app->config->email->smtp_username;
        $email->smtp_password = $app->config->email->smtp_password;

        $email->from_address = $app->config->email->service_address;
        $email->from_name = sprintf(Site::_('%s Customer Service'), $title);
        $email->subject = sprintf(Site::_('Reset Your %s Password'), $title);

        $email->send();
    }

    protected function getResetPasswordFields()
    {
        return ['text:password_tag'];
    }

    protected function getResetPasswordUri(SiteApplication $app)
    {
        return $app->getBaseHref() . 'account/resetpassword/' .
            $this->password_tag;
    }

    protected function getResetPasswordValues()
    {
        return ['password_tag' => SwatString::hash(
            openssl_random_pseudo_bytes(32)
        )];
    }

    protected function updateResetPasswordValues($values)
    {
        $this->password_tag = $values['password_tag'];
    }

    // generate password methods

    /**
     * Generates a password for this account, and saves it to the database.
     *
     * @param SiteApplication $app the application generating the new password
     */
    public function generatePassword(SiteApplication $app)
    {
        $this->checkDB();

        $id_field = new SwatDBField($this->id_field, 'integer');

        $fields = $this->getGeneratePasswordFields();
        $values = $this->getGeneratePasswordValues($app);

        // Update the database with new password. Don't use the regular
        // dataobject saving here in case other fields have changed.
        $transaction = new SwatDBTransaction($this->db);

        try {
            SwatDB::updateRow(
                $this->db,
                $this->table,
                $fields,
                $values,
                $id_field,
                $this->{$id_field->name}
            );

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        // Update the object's properties with the new values
        $this->updateGeneratePasswordValues($values);
    }

    /**
     * Emails this account's holder with his or her new generated password.
     *
     * @param SiteApplication $app the application sending mail
     *
     * @see SiteAccount::generatePassword()
     */
    public function sendGeneratePasswordMailMessage(SiteApplication $app)
    {
        if ($this->unencrypted_password == '') {
            throw new SiteException(Site::_(
                'This account’s password has not been updated. ' .
                'Before a new password mail message can be sent ' .
                'generatePassword() must be called.'
            ));
        }

        $title = $app->config->site->title;
        $password = $this->unencrypted_password;

        $email = new SiteNewPasswordMailMessage(
            $app,
            $this,
            $password,
            $title
        );

        $email->smtp_server = $app->config->email->smtp_server;
        $email->smtp_port = $app->config->email->smtp_port;
        $email->smtp_username = $app->config->email->smtp_username;
        $email->smtp_password = $app->config->email->smtp_password;

        $email->from_address = $app->config->email->service_address;
        $email->from_name = sprintf(Site::_('%s Customer Service'), $title);
        $email->subject = sprintf(Site::_('Your New %s Password'), $title);

        $email->send();
    }

    protected function getGeneratePasswordFields()
    {
        return ['text:password', 'text:password_salt'];
    }

    protected function getGeneratePasswordValues(SiteApplication $app)
    {
        $crypt = $app->getModule('SiteCryptModule');

        $password = Text_Password::Create();
        $password_hash = $crypt->generateHash($password);

        // Note: SiteAccount now uses crypt() for password hashing. The salt
        // is stored in the same field as the hashed password.
        return ['password_salt' => null, 'password' => $password_hash, 'unencrypted_password' => $password];
    }

    protected function updateGeneratePasswordValues($values)
    {
        // Note: SiteAccount now uses crypt() for password hashing. The salt
        // is stored in the same field as the hashed password.
        $this->password_salt = null;
        $this->unencrypted_password = $values['unencrypted_password'];

        $this->setPassword($values['password']);
    }

    // loader methods

    protected function loadLoginHistory()
    {
        $sql = sprintf(
            'select * from AccountLoginHistory
			where account = %s',
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(SiteAccountLoginHistoryWrapper::class)
        );
    }

    protected function loadLoginSessions()
    {
        $sql = sprintf(
            'select * from AccountLoginSession where account = %s
			order by login_date desc',
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(SiteAccountLoginSessionWrapper::class)
        );
    }
}
