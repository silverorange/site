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
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountEmailPassword extends AdminConfirmation
{
	// {{{ protected properties

	/**
	 * @var SiteAccount
	 */
	protected $account;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui->mapClassPrefixToPath('Site', 'Site');
		$this->ui->loadFromXML('Admin/pages/confirmation.xml');

		$this->id = SiteApplication::initVar('id');

		$sql = sprintf('select id, fullname, email, instance
			from Account where id = %s',
			$this->app->db->quote($this->id, 'integer'));

		$this->account = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('SiteAccountWrapper'))->getFirst();

		if ($this->account === null)
			throw new AdminNoAccessException();
		elseif ($this->app->hasModule('SiteMultipleInstanceModule') &&
			$this->account->instance != $this->app->instance->getInstance())
			throw new AdminNotFoundException(sprintf(
				Store::_('Incorrect instance for account ‘%d’.'),
					$this->id));
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processResponse()
	{
		$form = $this->ui->getWidget('confirmation_form');

		if ($form->button->id == 'yes_button') {
			$this->account->generateNewPassword($this->app);

			$message = new SwatMessage(sprintf(
				Site::_('%1$s’s password has been reset and has been emailed '.
				'to <a href="email:%2$s">%2$s</a>.'),
				$this->account->fullname, $this->account->email));

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

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('id', $this->id);

		$this->title = $this->account->fullname;

		$this->navbar->createEntry($this->account->fullname,
			sprintf('Account/Details?id=%s', $this->id));

		$this->navbar->createEntry(Site::_('Email New Password Confirmation'));

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $this->getConfirmationMessage();
		$message->content_type = 'text/xml';

		$this->ui->getWidget('yes_button')->title =
			Site::_('Reset & Email Password');
	}

	// }}}
	// {{{ private function getConfirmationMessage()

	private function getConfirmationMessage()
	{
		ob_start();

		$confirmation_title = new SwatHtmlTag('h3');

		$confirmation_title->setContent(sprintf(
			Site::_('Are you sure you want to reset the password for %s?'),
			$this->account->fullname));

		$confirmation_title->display();

		$email_anchor = new SwatHtmlTag('a');
		$email_anchor->href = sprintf('mailto:%s', $this->account->email);
		$email_anchor->setContent($this->account->email);

		ob_start();
		$email_anchor->display();
		$email_tag = ob_get_clean();

		printf(Site::_('A new password will be generated and sent to %s.'),
			$email_tag);

		return ob_get_clean();
	}

	// }}}
}

?>
