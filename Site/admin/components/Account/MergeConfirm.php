<?php

require_once 'Admin/pages/AdminDBConfirmation.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * Account merge confirmation page
 *
 * @package   Site
 * @copyright 2017 silverorange
 */
abstract class SiteAccountMergeConfirm extends AdminDBConfirmation
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var SiteAccount
	 */
	protected $account;

	/**
	 * @var integer
	 */
	protected $id2;

	/**
	 * @var SiteAccount
	 */
	protected $account2;

	/**
	 * @var boolean
	 */
	protected $keep_first;

	// }}}
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->id = SiteApplication::initVar('id');
		$this->account = $this->getAccount($this->id, $this->account);

		$this->id2 = SiteApplication::initVar('id2');
		$this->account2 = $this->getAccount($this->id2, $this->account2);

		$this->keep_first = SiteApplication::initVar('keep_first', false);
	}

	// }}}
	// {{{ protected function getAccount()

	protected function getAccount($id)
	{
		$account_class = SwatDBClassMap::get('SiteAccount');

		$account = new $account_class();
		$account->setDatabase($this->app->db);

		if (!$account->load($id)) {
			throw new AdminNotFoundException(sprintf(
				Site::_('An account with an id of ‘%d’ does not exist.'),
				$id
			));
		}

		$instance_id = $this->app->getInstanceId();
		if ($instance_id !== null) {
			if ($account->instance->id !== $instance_id) {
				throw new AdminNotFoundException(sprintf(
					Site::_('Incorrect instance for account ‘%d’.'),
					$id
				));
			}
		}

		return $account;
	}

	// }}}
	// {{{ protected function getSourceAccount()

	protected function getSourceAccount()
	{
		if (!$this->keep_first) {
			return $this->account;
		}
		return $this->account2;
	}

	// }}}
	// {{{ protected function getTargetAccount()

	protected function getTargetAccount()
	{
		if ($this->keep_first) {
			return $this->account;
		}
		return $this->account2;
	}

	// }}}
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$source_account = $this->getSourceAccount();
		$target_account = $this->getTargetAccount();

		$transaction = new SwatDBTransaction($this->app->db);
		try {
			$this->mergeAccounts($source_account, $target_account);

			$now = new SwatDate();
			$now->toUTC();
			$source_account->delete_date = $now;
			$this->addNote($source_account, $target_account);

			$target_account->delete_date = null;
			$this->addNote($target_account, $source_account);

			$transaction->commit();
		} catch (SwatDBException $e) {
			$transaction->rollback();
			throw $e;
		}

		$message = new SwatMessage(
			sprintf(
				Site::_(
					'Successfully merged the account “%s” into “%s”.'
				),
				$source_account->email,
				$target_account->email
			)
		);

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ abstract protected function mergeAccounts()

	abstract protected function mergeAccounts(
		SiteAccount $source_account,
		SiteAccount $target_account
	);

	// }}}
	// {{{ protected function addNote()

	protected function addNote(
		SiteAccount $this_account,
		SiteAccount $other_account
	) {
		$note = sprintf('Merged with account %s.', $other_account->email);
		if ($this_account->notes != '') {
			$this_account->notes .= ' '.$note;
		} else {
			$this_account->notes = $note;
		}
		$this_account->save();
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$this->app->relocate(sprintf('Account/Details?id=%s', $this->id));
	}

	// }}}
	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		ob_start();

		echo '<h3>';
		printf(
			Site::_(
				'Are you sure you want to merge the account %s into %s?'
			),
			SwatString::minimizeEntities($this->getSourceAccount()->email),
			SwatString::minimizeEntities($this->getTargetAccount()->email)
		);
		echo '</h3>';

		echo '<p>';
		printf(
			Site::_(
				'This will transfer all items from %s to %s and deactivate '.
				'the unused account.'
			),
			SwatString::minimizeEntities($this->getSourceAccount()->email),
			SwatString::minimizeEntities($this->getTargetAccount()->email)
		);
		echo '</p>';

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = ob_get_clean();
		$message->content_type = 'text/xml';

		$this->ui->getWidget('yes_button')->title =
			Site::_('Merge Accounts');
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('id', $this->id);
		$form->addHiddenField('id2', $this->id2);
		$form->addHiddenField('keep_first', $this->keep_first);
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntry();

		$this->navbar->createEntry(
			$this->account->fullname,
			sprintf('Account/Details?id=%s', $this->id)
		);

		$this->navbar->createEntry(
			Site::_('Merge'),
			sprintf('Account/Merge?id=%s', $this->id)
		);


		$this->navbar->createEntry(
			sprintf(Site::_('Merge With %s'), $this->account2->fullname),
			sprintf('Account/MergeSummary?id=%s&id2=%s', $this->id, $this->id2)
		);

		$this->navbar->createEntry(Site::_('Confirm'));
	}

	// }}}
}
?>
