<?php

require_once 'Turing/TuringSeleniumTest.php';

/**
 * Test account login, creation, logout
 *
 * @package   Site
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteAccountTest extends TuringSeleniumTest
{
	// {{{ protected properties

	protected static $account_name     = 'Selenium Tester';
	protected static $account_email    = null;
	protected static $account_password = 'test';

	// }}}
	// {{{ public function setUp()

	public function setUp()
	{
		if (self::$account_email === null) {
			self::$account_email = sprintf('%s@example.com', uniqid());
		}

		parent::setUp();
	}

	// }}}
	// {{{ abstract protected function getLoginPageUri()

	abstract protected function getLoginPageUri();

	// }}}
	// {{{ abstract protected function getNewAccountPageUri()

	abstract protected function getNewAccountPageUri();

	// }}}

	// tests
	// {{{ public function testBadLogin()

	public function testBadLogin()
	{
		$this->login('bogus@bogus.com', 'bogus');
		$this->assertTrue(
			$this->isTextPresent('Login Incorrect'),
			'Login incorrect message not present on bad login.'
		);
	}

	// }}}
	// {{{ public function testNewAccount()

	public function testNewAccount()
	{
		$this->open($this->getNewAccountPageUri());
		$this->assertNoErrors();

		$this->click(
			"xpath=//input[@type='submit' and @value='Create an Account']"
		);

		$this->waitForPageToLoad(30000);

		$this->type('fullname',         self::$account_name);
		$this->type('email',            self::$account_email);
		$this->type('confirm_email',    self::$account_email);
		$this->type('password',         self::$account_password);
		$this->type('confirm_password', self::$account_password);

		$this->click('submit_button');
		$this->waitForPageToLoad(30000);

		$this->assertAccountDetails();
	}

	// }}}
	// {{{ public function testLogin()

	public function testLogin()
	{
		$this->login(self::$account_email, self::$account_password);
		$this->assertAccountDetails();
	}

	// }}}
	// {{{ public function testChangePassword()

	public function testChangePassword()
	{
		$this->login(self::$account_email, self::$account_password);

		$new_password = 'test2';

		$this->click('link=Change Login Password');
		$this->waitForPageToLoad(30000);
		$this->assertNoErrors();

		// using xpaths because inputs have nonce ids for security
		$this->type(
			"xpath=//div[@id='old_password_field']/div[1]/input[1]",
			self::$account_password
		);

		$this->type(
			"xpath=//div[@id='password_field']/div[1]/input[1]",
			$new_password
		);

		$this->type(
			"xpath=//div[@id='confirm_password_field']/div[1]/input[1]",
			$new_password
		);

		$this->click('submit_button');
		$this->waitForPageToLoad(30000);
		$this->assertNoErrors();

		$this->assertTrue(
			$this->isTextPresent('Account password has been updated.'),
			'Password updated message not present.'
		);

		// logout
		$this->logout();
		$this->assertTrue(
			$this->isTextPresent('Account Login'),
			'Did not end up on login page after logging out of account page.'
		);

		// attempt old password
		$this->login(self::$account_email, self::$account_password);
		$this->assertTrue(
			$this->isTextPresent('Login Incorrect'),
			'Login incorrect message not present logging in with old password.'
		);

		// login with new password
		$this->login(self::$account_email, $new_password);
		$this->assertAccountDetails();

		$this->logout();
	}

	// }}}

	// helper methods
	// {{{ protected function assertAccountDetails()

	protected function assertAccountDetails()
	{
		$this->assertTrue(
			$this->isTextPresent(self::$account_name),
			'Account name not present on account details page.'
		);

		$this->assertTrue(
			$this->isTextPresent(self::$account_email),
			'Email address not present on account details page.'
		);
	}

	// }}}

	// think about moving these to a helper class
	// also think aboout clearCart() and addItemToCart()
	// {{{ protected function login()

	protected function login($email, $password)
	{
		$this->open($this->getLoginPageUri());

		$this->assertNoErrors();

		$this->type('email_address', $email);
		$this->type('password', $password);
		$this->click('login_button');
		$this->waitForPageToLoad(30000);

		$this->assertNoErrors();
	}

	// }}}
	// {{{ protected function logout()

	protected function logout()
	{
		$logout_button = "xpath=//input[@type='submit' and @value='Logout']";
		if ($this->isElementPresent($logout_button)) {
			$this->click($logout_button);
			$this->waitForPageToLoad(30000);
			$this->assertNoErrors();
		}
	}

	// }}}
}

?>
