<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Swat/SwatDate.php';

/**
 * Edit page for Accounts
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAccountEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $account;

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/admin/components/Account/edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initAccount();

		$this->ui->mapClassPrefixToPath('Site', 'Site');
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}
	// {{{ protected function initAccount()

	protected function initAccount()
	{
		$account_class = SwatDBClassMap::get('SiteAccount');

		$this->account = new $account_class();
		$this->account->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->account->load($this->id)) {
				throw new AdminNotFoundException(sprintf(
					Site::_('A account with an id of ‘%d’ does not exist.'),
					$this->id));
			}

            $instance_id = $this->app->getInstanceId();
            if ($instance_id !== null) {
                if ($this->account->instance->id !== $instance_id)
                    throw new AdminNotFoundException(sprintf(Store::_(
                        'Incorrect instance for account ‘%d’.'), $this->id));
            }
		} elseif ($this->app->hasModule('SiteMultipleInstanceModule')) {
			$this->account->instance = $this->app->instance->getInstance();
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$email = $this->ui->getWidget('email');
		if ($email->hasMessage())
			return;

		$instance = ($this->app->hasModule('SiteMultipleInstanceModule')) ?
			$this->app->instance->getInstance() : null;

		$class_name = SwatDBClassMap::get('SiteAccount');
		$account = new $class_name();
		$account->setDatabase($this->app->db);
		$found = $account->loadWithEmail($email->value, $instance);

		if ($found && $this->account->id !== $account->id) {
			$message = new SwatMessage(
				Site::_('An account already exists with this email address.'),
				'error');

			$message->content_type = 'text/xml';
			$email->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updateAccount();

		if ($this->id === null) {
			$now = new SwatDate();
			$now->toUTC();
			$this->account->createdate = $now;
		}

		$this->account->save();

		$this->updateBindings();

		$message = new SwatMessage(sprintf(
			Site::_('Account “%s” has been saved.'),
				$this->account->getFullname()));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function updateAccount()

	protected function updateAccount()
	{
		$this->account->email = $this->ui->getWidget('email')->value;
		$this->account->fullname = $this->ui->getWidget('fullname')->value;
	}

	// }}}
	// {{{ protected function updateBindings()

	protected function updateBindings()
	{
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$this->app->relocate('Account/Details?id='.$this->account->id);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->account));
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		if ($this->id === null) {
			$this->navbar->addEntry(new SwatNavBarEntry(Site::_('New Account')));
			$this->title = Site::_('New Account');
		} else {
			$this->navbar->addEntry(new SwatNavBarEntry(
				$this->account->getFullname(),
				sprintf('Account/Details?id=%s', $this->id)));
			$this->navbar->addEntry(new SwatNavBarEntry(Site::_('Edit')));
			$this->title = $this->account->getFullname();
		}
	}

	// }}}
}

?>
