<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';
require_once 'Site/SiteNewPasswordMailMessage.php';
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
 *   {@link SiteAccount::load()} or {@link SiteAccount::loadWithEmail}
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
 * // using loadWithEmail()
 * $account = new SiteAccount();
 * if ($account->loadWithEmail('test@example.com') &&
 *     $account->isCorrectPassword('secretpassword')) {
 *
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
 * @copyright 2005-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteAccountWrapper
 */
class SiteAccount extends SwatDBDataObject
{
	// {{{ class constants

	/**
	 * The salt length determines how much extra entropy is added to the hash
	 */
	const PASSWORD_SALT_LENGTH = 16;

	/**
	 * The prefix used by crypt to determine which hash to use
	 */
	const PASSWORD_CRYPT_PREFIX = '$6$';

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
	 * The password for this account
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

	// }}}
	// {{{ public function loadWithEmail()

	/**
	 * Loads an account from the database with just an email address
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
		$history = $this->getNewLoginHistory($date);
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
	// {{{ protected function getNewLoginHistory()

	protected function getNewLoginHistory(SwatDate $date)
	{
		$class = SwatDBClassMap::get('SiteAccountLoginHistory');

		$history = new $class();
		$history->setDatabase($this->db);

		$history->account = $this->id;
		$history->login_date = $date;
		$history->ip_address = substr($_SERVER['REMOTE_ADDR'], 0, 15);

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
				$user_agent = substr($user_agent, 0, 255);
				$history->user_agent = $user_agent;
			}

		}

		return $history;
	}

	// }}}

	// password methods
	// {{{ public function setPassword()

	/**
	 * Sets this account's password
	 *
	 * @param string $password the plaintext password for this account.
	 */
	public function setPassword($password)
	{
		$this->password = $this->calculatePasswordHash($password);

		// Note: SiteAccount now uses crypt() for password hashing. The salt
		// is stored in the same field as the hashed password.
		$this->password_salt = null;
	}

	// }}}
	// {{{ public function isCorrectPassword()

	/**
	 * Checks if the given password is this account's password
	 *
	 * @return boolean true if passwords match, false if not.
	 */
	public function isCorrectPassword($password)
	{
		if ($this->isCryptPassword()) {
			$hash = crypt($password, $this->password);
		} else {
			$salt = base64_decode($this->password_salt, true);

			// salt may not be base64 encoded
			if ($salt === false) {
				$salt = $this->password_salt;
			}

			$hash = md5($password.$salt);
		}

		return ($this->password === $hash);
	}

	// }}}
	// {{{ public function isCryptPassword()

	/**
	 * Checks if this account's password has been hashed by crypt(3)
	 *
	 * @return boolean true if the password was hashed with crypt(3)
	 */
	public function isCryptPassword()
	{
		return (substr($this->password, 0, 3) === self::PASSWORD_CRYPT_PREFIX);
	}

	// }}}
	// {{{ protected function calculatePasswordHash()

	/**
	 * Calculates the password hash for a password
	 *
	 * @param string $password the plaintext password.
	 *
	 * @return string the hashed password.
	 */
	protected function calculatePasswordHash($password)
	{
		$salt = self::PASSWORD_CRYPT_PREFIX.
			SwatString::getCryptSalt(self::PASSWORD_SALT_LENGTH);

		return crypt($password, $salt);
	}

	// }}}

	// reset password methods
	// {{{ public function resetPassword()

	/**
	 * Creates a unique tag that can be used to reset this account's password
	 *
	 * @param SiteApplication $app the application generating the new password.
	 */
	public function resetPassword(SiteApplication $app)
	{
		$this->checkDB();

		$id_field = new SwatDBField($this->id_field, 'integer');

		$fields = $this->getResetPasswordFields();
		$values = $this->getResetPasswordValues($app);

		// Update the database with new password tag. Don't use the regular
		// dataobject saving here in case other fields have changed.
		$transaction = new SwatDBTransaction($this->db);
		try {
			SwatDB::updateRow($this->db, $this->table, $fields, $values,
				$id_field, $this->{$id_field->name});

			$transaction->commit();
		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}

		// Update the object's properties with the new values
		$this->updateResetPasswordValues($values);
	}

	// }}}
	// {{{ public function sendResetPasswordMailMessage()

	/**
	 * Emails this account's holder with instructions on how to finish
	 * resetting his or her password
	 *
	 * @param SiteApplication $app the application sending mail.
	 *
	 * @see StoreAccount::resetPassword()
	 */
	public function sendResetPasswordMailMessage(SiteApplication $app)
	{
		if ($this->password_tag == '') {
			throw new SiteException(Site::_(
				'This account’s password tag has not been set. '.
				'Before a reset password mail message can be sent '.
				'resetPassword() must be called.'));
		}

		$title = $app->config->site->title;
		$link = $app->getBaseHref().'account/resetpassword/'.
			$this->password_tag;

		$email = new SiteResetPasswordMailMessage(
			$app, $this, $link, $title);

		$email->smtp_server = $app->config->email->smtp_server;
		$email->from_address = $app->config->email->service_address;
		$email->from_name = sprintf('%s Customer Service', $title);
		$email->subject = sprintf('Reset Your %s Password', $title);

		$email->send();
	}

	// }}}
	// {{{ protected function getResetPasswordFields()

	protected function getResetPasswordFields()
	{
		return array(
			'text:password_tag',
		);
	}

	// }}}
	// {{{ protected function getResetPasswordValues()

	protected function getResetPasswordValues()
	{
		return array(
			'password_tag' => SwatString::hash(uniqid(rand(), true)),
		);
	}

	// }}}
	// {{{ protected function updateResetPasswordValues()

	protected function updateResetPasswordValues($values)
	{
		$this->password_tag = $values['password_tag'];
	}

	// }}}

	// generate password methods
	// {{{ public function generatePassword()

	/**
	 * Generates a password for this account, and saves it to the database
	 *
	 * @param SiteApplication $app the application generating the new password.
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
			SwatDB::updateRow($this->db, $this->table, $fields, $values,
				$id_field, $this->{$id_field->name});

			$transaction->commit();
		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}

		// Update the object's properties with the new values
		$this->updateGeneratePasswordValues($values);
	}

	// }}}
	// {{{ public function sendGeneratePasswordMailMessage()

	/**
	 * Emails this account's holder with his or her new generated password
	 *
	 * @param SiteApplication $app the application sending mail.
	 *
	 * @see SiteAccount::generatePassword()
	 */
	public function sendGeneratePasswordMailMessage(SiteApplication $app)
	{
		if ($this->unencrypted_password == '') {
			throw new SiteException(Site::_(
				'This account’s password has not been updated. '.
				'Before a new password mail message can be sent '.
				'generatePassword() must be called.'));
		}

		$title = $app->config->site->title;
		$password = $this->unencrypted_password;

		$email = new SiteNewPasswordMailMessage(
			$app, $this, $password, $title);

		$email->smtp_server = $app->config->email->smtp_server;
		$email->from_address = $app->config->email->service_address;
		$email->from_name = sprintf('%s Customer Service', $title);
		$email->subject = sprintf('Your New %s Password', $title);

		$email->send();
	}

	// }}}
	// {{{ protected function getGeneratePasswordFields()

	protected function getGeneratePasswordFields()
	{
		return array(
			'text:password',
			'text:password_salt',
		);
	}

	// }}}
	// {{{ protected function getGeneratePasswordValues()

	protected function getGeneratePasswordValues(SiteApplication $app)
	{
		require_once 'Text/Password.php';

		$password      = Text_Password::Create();
		$password_hash = $this->calculatePasswordHash($password);

		// Note: SiteAccount now uses crypt() for password hashing. The salt
		// is stored in the same field as the hashed password.
		return array(
			'password_salt'        => null,
			'password'             => $password_hash,
			'unencrypted_password' => $password,
		);
	}

	// }}}
	// {{{ protected function updateGeneratePasswordValues()

	protected function updateGeneratePasswordValues($values)
	{
		// Note: SiteAccount now uses crypt() for password hashing. The salt
		// is stored in the same field as the hashed password.
		$this->password_salt        = null;
		$this->password             = $values['password'];
		$this->unencrypted_password = $values['unencrypted_password'];
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
