<?php

require_once 'Admin/pages/AdminConfirmation.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'Site/dataobjects/SiteAccount.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Page to generate a new password for an account and email the new password
 * to the account holder
 *
 * @package   Site
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountForgotPassword extends AdminConfirmation
{
	// {{{ protected properties

	/**
	 * @var SiteAccount
	 */
	protected $account;

	/**
	 * @var Array
	 */
	protected $ui_strings = [];

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui->mapClassPrefixToPath('Site', 'Site');
		$this->ui->loadFromXML('Admin/pages/confirmation.xml');

		$this->id = SiteApplication::initVar('id');

		$this->account = $this->getAccount();
	}

	// }}}
	// {{{ protected function getAccount()

	protected function getAccount()
	{
		if ($this->account === null) {
			$account_class = SwatDBClassMap::get('SiteAccount');

			$this->account = new $account_class();
			$this->account->setDatabase($this->app->db);

			if (!$this->account->load($this->id)) {
				throw new AdminNotFoundException(sprintf(
					Site::_('A account with an id of ‘%d’ does not exist.'),
					$this->id));
			}

			$instance_id = $this->app->getInstanceId();
			if ($instance_id !== null) {
				if ($this->account->instance->id !== $instance_id) {
					throw new AdminNotFoundException(sprintf(Store::_(
						'Incorrect instance for account ‘%d’.'), $this->id));
				}
			}
		}

		return $this->account;
	}

	// }}}

	// process phase
	// {{{ protected function processResponse()

	protected function processResponse()
	{
		$form = $this->ui->getWidget('confirmation_form');

		if ($form->button->id == 'yes_button') {
			$this->account->resetPassword($this->app);
			$this->account->sendResetPasswordMailMessage($this->app);

			$message = new SwatMessage(
				sprintf(
					Site::_(
					'A password-reset email has been sent to'.
					' <a href="mailto:%1$s">%1$s</a>.'
					),
					SwatString::minimizeEntities($this->account->email)
				)
			);

			$message->content_type = 'text/xml';
			$this->app->messages->add($message);
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$strings = $this->getUiStrings();

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('id', $this->id);

		$this->title = $this->account->getFullname();

		$this->navbar->createEntry($this->account->getFullname(),
			sprintf('Account/Details?id=%s', $this->id));

		$this->navbar->createEntry($strings['nav_bar']);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $this->getConfirmationMessage();
		$message->content_type = 'text/xml';

		$this->ui->getWidget('yes_button')->title = $strings['yes_button'];
	}

	// }}}
	// {{{ private function getConfirmationMessage()

	private function getConfirmationMessage()
	{
		$strings = $this->getUiStrings();

		ob_start();

		$confirmation_title = new SwatHtmlTag('h3');

		$confirmation_title->setContent($strings['confirmation_title']);

		$confirmation_title->display();

		$email_anchor = new SwatHtmlTag('a');
		$email_anchor->href = sprintf('mailto:%s', $this->account->email);
		$email_anchor->setContent($this->account->email);

		ob_start();
		$email_anchor->display();
		$email_tag = ob_get_clean();

		printf($strings['confirmation_message'], $email_tag);

		return ob_get_clean();
	}

	// }}}
    // {{{ protected function getUiStrings()

    protected function getUiStrings()
    {
        if (empty($this->uiStrings)) {
            $this->uiStrings['yes_button'] = Site::_(
                'Send a Password-Reset Email'
            );

            $this->uiStrings['nav_bar'] = Site::_(
                'Email Password Reset Confirmation'
            );

            $this->uiStrings['confirmation_message'] = Site::_(
                'The email will be sent to %s.'
            );

            $this->uiStrings['confirmation_title'] = sprintf(
                Site::_(
                    'Are you sure you want to send a password-reset email to'.
                    ' %s?'
                ),
                $this->account->getFullname()
            );
        }

        return $this->uiStrings;
    }

    // }}}
}

?>
