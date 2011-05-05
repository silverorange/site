<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';
require_once 'Site/SiteResetPasswordMailMessage.php';
require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteAccountWrapper.php';
require_once 'Site/dataobjects/SiteAccountLoginHistoryWrapper.php';

/**
 * A account for a web application
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
 * $new_account = new SiteAccount();
 * $new_account->email = 'account@example.com';
 * $new_account->setPassword('secretpassword');
 * $new_account->save();
 * </code>
 *
 * - Create a new SiteAccount object with a blank constructor. Call the
 *   {@link SiteAccount::load()} or {@link SiteAccount::loadWithCredentials}
 *   method on the object instance. Modify some properties and call the save()
 *   method. The modified properties are updated in the database.
 *
 * <code>
 * // using regular data-object load() method
 * $account = new SiteAccount();
 * $account->load(123);
 * echo 'Hello ' . $account->fullname;
 * $account->email = 'new_address@example.com';
 * $account->save();
 *
 * // using loadWithCredentials()
 * $account = new SiteAccount();
 * if ($account->loadWithCredentials('test@example.com', 'secretpassword')) {
 *     echo 'Hello ' . $account->fullname;
 *     $account->email = 'new_address@example.com';
 *     $account->save();
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
 * echo 'Hello ' . $account->fullname;
 * $account->email = 'new_address@example.com';
 * $account->save();
 * </code>
 *
 * @package   Site
 * @copyright 2005-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAccountWrapper
 */
class SiteAccount extends SwatDBDataObject
{
	// {{{ class constants

	/**
	 * Length of salt value used to protect accounts's passwords from
	 * dictionary attacks
	 */
	const PASSWORD_SALT_LENGTH = 16;

	// }}}
	// {{{ public properties

	/**
	 * The database id of this account
	 *
	 * If this property is null or 0 when SiteAccount::save() method is
	 * called, a new account is inserted in the database.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * The full name of this account
	 *
	 * @var string
	 */
	public $fullname;

	/**
	 * The email address of this account
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Hashed version of this account's salted password
	 *
	 * By design, there is no way to get the actual password of this account
	 * through the SiteAccount object.
	 *
	 * @var string
	 *
	 * @see SiteAccount::setPassword()
	 */
	public $password;

	/**
	 * The salt value used to protect this account's password base-64 encoded
	 *
	 * @var string
	 */
	public $password_salt;

	/**
	 * Hashed password tag for reseting the account password
	 *
	 * @var text
	 */
	public $password_tag;

	/**
	 * The date this account was created on
	 *
	 * @var SwatDate
	 */
	public $createdate;

	/**
	 * The last date on which this account was logged into
	 *
	 * @var SwatDate
	 */
	public $last_login;

	// }}}
	// {{{ protected properties

	/**
	 * @see SiteAccount::getSuspiciousActivity()
	 */
	protected $suspicious_activity;

	// }}}
	// {{{ public function loadWithCredentials()

	/**
	 * Loads an acount from the database with account credentials
	 *
	 * @param string $email the email address of the account.
	 * @param string $password the password of the account.
	 * @param SiteInstance $instance Optional site instance for this account.
	 *                               Used when the site implements
	 *                               {@link SiteMultipleInstanceModule}.
	 *
	 * @return boolean true if the loading was successful and false if it was
	 *                  not.
	 *
	 * @sensitive $password
	 */
	public function loadWithCredentials($email, $password,
		SiteInstance $instance = null)
	{
		$this->checkDB();

		$sql = sprintf('select password_salt from %s
			where lower(email) = lower(%s)',
			$this->table,
			$this->db->quote($email, 'text'));

		if ($instance !== null)
			$sql.= sprintf(' and instance = %s',
				$this->db->quote($instance->id, 'integer'));

		$salt = SwatDB::queryOne($this->db, $sql);

		if ($salt === null)
			return false;

		// salt might be base-64 encoded
		$decoded_salt = base64_decode($salt, true);
		if ($decoded_salt !== false)
			$salt = $decoded_salt;

		$sql = sprintf('select id from %s
			where lower(email) = lower(%s) and password = %s',
			$this->table,
			$this->db->quote($email, 'text'),
			$this->db->quote(md5($password.$salt), 'text'));

		if ($instance !== null)
			$sql.= sprintf(' and instance = %s',
				$this->db->quote($instance->id, 'integer'));

		$id = SwatDB::queryOne($this->db, $sql);

		if ($id === null)
			return false;

		return $this->load($id);
	}

	// }}}
	// {{{ public function loadWithEmail()

	/**
	 * Loads an acount from the database with just an email address
	 *
	 * This is useful for password recovery and email address verification.
	 *
	 * @param string $email the email address of the account.
	 * @param SiteInstance $instance Optional site instance for this account.
	 *                               Used when the site implements
	 *                               {@link SiteMultipleInstanceModule}.
	 *
	 * @return boolean true if the loading was successful and false if it was
	 *                  not.
	 */
	public function loadWithEmail($email, SiteInstance $instance = null)
	{
		$this->checkDB();

		$sql = sprintf('select id from %s
			where lower(email) = lower(%s)',
			$this->table,
			$this->db->quote($email, 'text'));

		if ($instance !== null)
			$sql.= sprintf(' and instance = %s',
				$this->db->quote($instance->id, 'integer'));

		$id = SwatDB::queryOne($this->db, $sql);

		if ($id === null)
			return false;

		return $this->load($id);
	}

	// }}}
	// {{{ public function getFullName()

	/**
	 * Gets the full name of the person who owns this account
	 *
	 * Having this method allows subclasses to split the full name into an
	 * arbitrary number of fields. For example, first name and last name.
	 *
	 * @return string the full name of the person who owns this account.
	 */
	public function getFullName()
	{
		return $this->fullname;
	}

	// }}}
	// {{{ public function updateLastLoginDate()

	/**
	 * Sets the last login date of this account in the database to the
	 * specified date and also records the login in the
	 * AccountLoginHistory table.
	 *
	 * @param SwatDate $date the last login date of this account.
	 */
	public function updateLastLoginDate(SwatDate $date)
	{
		$this->checkDB();

		// old way: update last_login date field
		$id_field = new SwatDBField($this->id_field, 'integer');
		$sql = sprintf('update %s set last_login = %s where %s = %s',
			$this->table,
			$this->db->quote($date->getDate(), 'date'),
			$id_field->name,
			$this->db->quote($this->getId(), $id_field->type));

		SwatDB::exec($this->db, $sql);

		// new way: save to history table
		$class = SwatDBClassMap::get('SiteAccountLoginHistory');
		$history = new $class;
		$history->setDatabase($this->db);
		$history->account = $this->id;
		$history->login_date = $date;
		$history->ip_address = substr($_SERVER['REMOTE_ADDR'], 0, 15);

		if (isset($_SERVER['HTTP_USER_AGENT']))
			$history->user_agent =
				substr($_SERVER['HTTP_USER_AGENT'], 0, 255);

		$history->save();
	}

	// }}}
	// {{{ public function getSuspiciousActivity()

	public function getSuspiciousActivity()
	{
		if ($this->suspicious_activity === null) {
			$this->checkDB();

			$sql = sprintf(
				'select * from SuspiciousAccountView
				where
					account = %s
					and too_many_logins = %s
					and (ip_address_distinct = %s or user_agent_distinct = %s)',
				$this->db->quote($this->id, 'integer'),
				$this->db->quote(true, 'boolean'),
				$this->db->quote(true, 'boolean'),
				$this->db->quote(true, 'boolean'));

			$this->suspicious_activity =
				SwatDB::query($this->db, $sql)->getFirst();
		}

		return $this->suspicious_activity;
	}

	// }}}
	// {{{ public static function getSuspiciousActivitySummary()

	public static function getSuspiciousActivitySummary($activity)
	{
		$locale = SwatI18NLocale::get();

		$summary = Site::_(
			'In the last week, 5 or more logins within a '.
			'1 hour period from '
		);

		if ($activity->user_agent_distinct) {
			$summary.= Site::_('2 or more user agents and ');
		} else {
			$summary.= Site::_('1 user agent and ');
		}

		if ($activity->ip_address_distinct) {
			$summary.= Site::_('2 or more IP addresses.');
		} else {
			$summary.= Site::_('1 IP address.');
		}

		return $summary;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Account';
		$this->id_field = 'integer:id';

		$this->registerDateProperty('createdate');
		$this->registerDateProperty('last_login');

		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));
	}

	// }}}

	// password methods
	// {{{ public function setPassword()

	/**
	 * Sets this account's password
	 *
	 * The password is salted with a 16-byte salt and encrypted with one-way
	 * encryption.  The salt is stored separately and is base-64 encoded.
	 *
	 * @param string $password the password for this account.
	 */
	public function setPassword($password)
	{
		$salt = SwatString::getSalt(self::PASSWORD_SALT_LENGTH);
		$this->password_salt = base64_encode($salt);
		$this->password = md5($password.$salt);
	}

	// }}}
	// {{{ public function resetPassword()

	/**
	 * Resets this account's password
	 *
	 * Creates a unique tag that can be emailed to the account holder as part of
	 * a url that allows them to update their password.
	 *
	 * @param SiteApplication $app the application resetting this account's
	 *                              password.
	 *
	 * @return string $password_tag a hashed tag to verify the account
	 */
	public function resetPassword(SiteApplication $app)
	{
		$this->checkDB();

		$password_tag = SwatString::hash(uniqid(rand(), true));

		/*
		 * Update the database with new password tag. Don't use the regular
		 * dataobject saving here in case other fields have changed.
		 */
		$id_field = new SwatDBField($this->id_field, 'integer');
		$sql = sprintf('update %s set password_tag = %s where %s = %s',
			$this->table,
			$this->db->quote($password_tag, 'text'),
			$id_field->name,
			$this->db->quote($this->{$id_field->name}, $id_field->type));

		SwatDB::exec($this->db, $sql);

		return $password_tag;
	}

	// }}}
	// {{{ public function generateNewPassword()

	/**
	 * Generates a new password for this account, saves it, and emails it to
	 * this account's holder
	 *
	 * @param SiteApplication $app the application generating the new password.
	 *
	 * @return string $new_password the new password in plaintext
	 *
	 * @see SiteAccount::resetPassword()
	 */
	public function generateNewPassword(SiteApplication $app)
	{
		require_once 'Text/Password.php';

		$new_password      = Text_Password::Create();
		$new_password_salt = SwatString::getSalt(self::PASSWORD_SALT_LENGTH);

		$transaction = new SwatDBTransaction($this->db);
		try {
			/*
			 * Update the database with new password. Don't use the regular
			 * dataobject saving here in case other fields have changed.
			 */
			$id_field = new SwatDBField($this->id_field, 'integer');

			$sql = sprintf(
				'update %s set password = %s, password_salt = %s where %s = %s',
				$this->table,
				$app->db->quote(md5($new_password.$new_password_salt), 'text'),
				$app->db->quote(base64_encode($new_password_salt), 'text'),
				$id_field->name,
				$this->db->quote($this->{$id_field->name}, $id_field->type)
			);

			SwatDB::exec($app->db, $sql);

			$transaction->commit();
		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}

		return $new_password;
	}

	// }}}
	// {{{ public function sendResetPasswordMailMessage()

	/**
	 * Emails this account's holder with instructions on how to finish
	 * resetting his or her password
	 *
	 * @param SiteApplication $app the application sending mail.
	 * @param string $password_link a URL indicating the page at which the
	 *                               account holder may complete the reset-
	 *                               password process.
	 *
	 * @see StoreAccount::resetPassword()
	 */
	public function sendResetPasswordMailMessage(
		SiteApplication $app, $password_link)
	{
	}

	// }}}
	// {{{ public function sendNewPasswordMailMessage()

	/**
	 * Emails this account's holder with his or her new generated password
	 *
	 * @param SiteApplication $app the application sending mail.
	 * @param string $new_password this account's new password.
	 *
	 * @see SiteAccount::generateNewPassword()
	 */
	public function sendNewPasswordMailMessage(
		SiteApplication $app, $new_password)
	{
	}

	// }}}

	// loader methods
	// {{{ protected function loadLoginHistory()

	protected function loadLoginHistory()
	{
		$sql = sprintf('select * from AccountLoginHistory
			where account = %s',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('SiteAccountLoginHistoryWrapper'));
	}

	// }}}
}

?>
