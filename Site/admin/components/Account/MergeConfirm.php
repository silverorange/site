<?php

require_once 'Admin/pages/AdminDBConfirmation.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * Account merge confirmation page
 *
 * @package   Site
 * @copyright 2016 silverorange
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

		$this->keep_first = SiteApplication::initVar('keep_first');
	}

	// }}}
	// {{{ protected function getAccount()

	protected function getAccount($id, $account)
	{
		if ($account === null) {
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
						Store::_('Incorrect instance for account ‘%d’.'),
						$id
					));
				}
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
		$this->mergeAccounts();

		$message = new SwatMessage(
			sprintf(
				Rap::_(
					'Successfully merged the account "%s" into "%s".'
				),
				SwatString::minimizeEntities($source_account->email),
				SwatString::minimizeEntities($target_account->email)
			)
		);

		$this->app->messages->add($message);
	}
	// }}}
	// {{{ abstract protected function mergeAccounts()
	abstract protected function mergeAccounts();

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$this->app->relocate('Account');
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
			Rap::_(
				'Are you sure you want to merge the account %s into %s?'
			),
			SwatString::minimizeEntities($this->getSourceAccount()->email),
			SwatString::minimizeEntities($this->getTargetAccount()->email)
		);
		echo '</h3>';

		echo '<p>';
		printf(
			Rap::_(
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
			Rap::_('Merge Accounts');
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

		$this->navbar->addEntry(new SwatNavBarEntry(
			$this->account->fullname,
			sprintf('Account/Details?id=%s', $this->id)
		));

		$this->navbar->addEntry(new SwatNavBarEntry(
			Site::_('Merge'),
			sprintf('Account/Merge?id=%s', $this->id)
		));


		$this->navbar->addEntry(new SwatNavBarEntry(
			sprintf(Site::_('Merge With %s'), $this->account2->fullname),
			sprintf('Account/MergeSummary?id=%s&id2=%s', $this->id, $this->id2)
		));

		$this->navbar->createEntry(Rap::_('Confirm'));
	}
	// }}}
}
?>