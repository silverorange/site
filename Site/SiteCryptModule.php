<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Site/exceptions/SiteException.php';

/**
 * Application module for crypt based password hashing
 *
 * @package   Site
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteCryptModule extends SiteApplicationModule
{
	// {{{ class constants

	const SHA_MIN_ROUNDS = 1000;
	const SHA_MAX_ROUNDS = 999999999;
	const SHA_SALT_LENGTH = 16;

	const BLOWFISH_MIN_ROUNDS = 4;
	const BLOWFISH_MAX_ROUNDS = 31;
	const BLOWFISH_SALT_LENGTH = 22;

	// }}}
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $method;

	/**
	 * @var string
	 */
	protected $rounds;

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this module
	 */
	public function init()
	{
		$config = $this->app->getModule('SiteConfigModule');

		$method = $config->crypt->method;
		$rounds = $config->crypt->rounds;

		if (
			$method !== 'sha256' &&
			$method !== 'sha512' &&
			$method !== 'blowfish'
		) {
			throw new SiteException(
				sprintf(
					'The password hashing method “%s” is not valid.',
					$method
				)
			);
		}

		$this->method = $method;
		$this->rounds = (int)$rounds;
	}

	// }}}
	// {{{ public function verifyHash()

	/**
	 * Checks if the given password matches the given password hash
	 *
	 * @return boolean true if passwords match, false if not.
	 */
	public function verifyHash($password, $password_hash, $password_salt)
	{
		// If the password isn't a crypt style password than it's in the old
		// style form of a salted MD5 hash.
		if (mb_substr($password_hash, 0, 1) === '$') {
			$hash = crypt($password, $password_hash);
		} else {
			$salt = base64_decode($password_salt, true);

			// salt may not be base64 encoded
			if ($salt === false) {
				$salt = $password_salt;
			}

			$hash = md5($password.$salt);
		}

		return ($password_hash === $hash);
	}

	// }}}
	// {{{ public function shouldUpdateHash()

	/**
	 * Checks if the given password hash should be re-hashed
	 *
	 * @return boolean true if password hash should be updated.
	 */
	public function shouldUpdateHash($password_hash)
	{
		$parts = explode('$', $password_hash);

		// Old MD5 password hashes should always be updated.
		if (count($parts) < 3) {
			return true;
		}

		$method = $parts[1];
		$rounds = $parts[2];

		return (
			$this->getCryptMethod() !== $method ||
			$this->getCryptRounds() !== $rounds
		);
	}

	// }}}
	// {{{ public function generateHash()

	/**
	 * Generates a password hash for the given password
	 *
	 * @return string the password hash for the given password
	 */
	public function generateHash($password)
	{
		return crypt($password, $this->generateSalt());
	}

	// }}}
	// {{{ protected function getCryptMethod()

	protected function getCryptMethod()
	{
		switch ($this->method) {
		case 'sha256':
			return '5';

		case 'sha512':
			return '6';

		case 'blowfish':
			return '2y';

		default:
			throw new SiteException(
				'The password hashing method “%s” is not valid.',
				$this->method
			);
		}
	}

	// }}}
	// {{{ protected function getCryptRounds()

	protected function getCryptRounds()
	{
		switch ($this->method) {
		case 'sha256':
		case 'sha512':
			$rounds = $this->rounds;
			$rounds = max($rounds, self::SHA_MIN_ROUNDS);
			$rounds = min($rounds, self::SHA_MAX_ROUNDS);

			return 'rounds='.$rounds;

		case 'blowfish':
			$rounds = $this->rounds;
			$rounds = max($rounds, self::BLOWFISH_MIN_ROUNDS);
			$rounds = min($rounds, self::BLOWFISH_MAX_ROUNDS);

			return ($rounds < 10) ? '0'.$rounds : (string)$rounds;

		default:
			throw new SiteException(
				'The password hashing method “%s” is not valid.',
				$this->method
			);
		}
	}

	// }}}
	// {{{ protected function getCryptSaltLength()

	protected function getCryptSaltLength()
	{
		switch ($this->method) {
		case 'sha256':
		case 'sha512':
			return self::SHA_SALT_LENGTH;

		case 'blowfish':
			return self::BLOWFISH_SALT_LENGTH;

		default:
			throw new SiteException(
				'The password hashing method “%s” is not valid.',
				$this->method
			);
		}
	}

	// }}}
	// {{{ protected function generateSalt()

	protected function generateSalt()
	{
		return sprintf(
			'$%s$%s$%s$',
			$this->getCryptMethod(),
			$this->getCryptRounds(),
			$this->generateCryptSalt($this->getCryptSaltLength())
		);
	}

	// }}}
	// {{{ protected function generateCryptSalt()

	/**
	 * Gets a salt value for crypt(3)
	 *
	 * This method generates a random ASCII string of the sepcified length.
	 * Only the following characters, [./0-9A-Za-z], are included in the
	 * returned string.
	 *
	 * @param integer $length the desired length of the crypt(3) salt.
	 *
	 * @return string a salt value of the specified length.
	 */
	protected function generateCryptSalt($length)
	{
		$length = max(0, intval($length));

		$salt = '';

		for ($i = 0; $i < $length; $i++) {
			$index = mt_rand(0, 63);

			if ($index >= 38) {
				$salt.= chr($index - 38 + 97);
			} else if ($index >= 12) {
				$salt.= chr($index - 12 + 65);
			} else {
				$salt.= chr($index + 46);
			}
		}

		return $salt;
	}

	// }}}
}

?>
