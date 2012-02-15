<?php

require_once 'Swat/SwatUI.php';
require_once 'Site/SiteApplication.php';
require_once 'Admin/AdminSearchClause.php';

/**
 * @package   Site
 * @copyright 2012 silverorange
 */
class SiteAccountSearch
{
	// {{{ protected properties

	/**
	 * @var SiteApplication
	 */
	protected $app;

	/**
	 * @var SwatUI
	 */
	protected $ui;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SwatUI $ui)
	{
		$this->app = $app;
		$this->ui = $ui;
	}

	// }}}
	// {{{ public function getJoinClause()

	public function getJoinClause()
	{
	}

	// }}}
	// {{{ public function getWhereClause()

	public function getWhereClause()
	{
		// The only way an account fullname can be null is if we've cleared
		// the data from it with the privacy scripts - we don't ever want to
		// display these accounts in the search results.
		$where = 'Account.fullname is not null';

		foreach ($this->getWhereClauses() as $clause) {
			$where.= $clause->getClause($this->app->db);
		}

		return $where;
	}

	// }}}
	// {{{ public function getOrderByClause()

	public function getOrderByClause()
	{
		return 'fullname, email';
	}

	// }}}
	// {{{ protected function getWhereClauses()

	protected function getWhereClauses()
	{
		$clauses = array();

		// instance
		$instance_id = $this->app->getInstanceId();
		if ($instance_id === null && $this->ui->hasWidget('search_instance')) {
			$instance_id = $this->ui->getWidget('search_instance')->value;
		}

		if ($instance_id !== null) {
			$clause = new AdminSearchClause('integer:instance');
			$clause->table = 'Account';
			$clause->value = $instance_id;
			$clauses['instance'] = $clause;
		}

		// fullname
		$clause = new AdminSearchClause('fullname');
		$clause->table = 'Account';
		$clause->value = $this->ui->getWidget('search_fullname')->value;
		$clause->operator = AdminSearchClause::OP_CONTAINS;
		$clauses['fullname'] = $clause;

		// email
		$clause = new AdminSearchClause('email');
		$clause->table = 'Account';
		$clause->value = $this->ui->getWidget('search_email')->value;
		$clause->operator = AdminSearchClause::OP_CONTAINS;
		$clauses['email'] = $clause;

		return $clauses;
	}

	// }}}
}

?>
