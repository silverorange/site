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

	protected $fields;

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

		$this->ui->mapClassPrefixToPath('Site', 'Site');
		$this->ui->loadFromXML($this->ui_xml);

		$this->fields = array('fullname', 'email');
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$email = $this->ui->getWidget('email');
		if ($email->hasMessage())
			return;

		$query = SwatDB::query($this->app->db, sprintf('select email
			from Account where lower(email) = lower(%s) and id %s %s',
			$this->app->db->quote($email->value, 'text'),
			SwatDB::equalityOperator($this->id, true),
			$this->app->db->quote($this->id, 'integer')));

		if (count($query) > 0) {
			$message = new SwatMessage(Site::_(
				'An account already exists with this email address.'),
				SwatMessage::ERROR);

			$email->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->getUIValues();

		if ($this->id === null) {
			$now = new SwatDate();
			$now->toUTC();
			$this->fields[] = 'date:createdate';
			$values['createdate'] = $now->getDate();

			$this->id = SwatDB::insertRow($this->app->db, 'Account',
				$this->fields, $values, 'id');

			$this->new_account = true;
		} else {
			SwatDB::updateRow($this->app->db, 'Account', $this->fields,
				$values, 'id', $this->id);
		}

		$message = new SwatMessage(sprintf(
			Site::_('Account “%s” has been saved.'), $values['fullname']));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function getUIValues()

	protected function getUIValues()
	{
		return $this->ui->getValues(array('fullname', 'email'));
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$this->app->relocate('Account/Details?id='.$this->id);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$row = SwatDB::queryRowFromTable($this->app->db, 'Account',
			$this->fields, 'id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf(Site::_("Account with id ‘%s’ not found."),
				$this->id));

		$this->ui->setValues(get_object_vars($row));
	}

	// }}}
	// {{{ private function buildNavBar()

	protected function buildNavBar()
	{
		if ($this->id === null) {
			$this->navbar->addEntry(new SwatNavBarEntry(Site::_('New Account')));
			$this->title = Site::_('New Account');
		} else {
			$account_fullname = SwatDB::queryOneFromTable($this->app->db,
				'Account', 'text:fullname', 'id', $this->id);

			$this->navbar->addEntry(new SwatNavBarEntry($account_fullname,
				sprintf('Account/Details?id=%s', $this->id)));
			$this->navbar->addEntry(new SwatNavBarEntry(Site::_('Edit')));
			$this->title = $account_fullname;
		}
	}

	// }}}
}

?>
