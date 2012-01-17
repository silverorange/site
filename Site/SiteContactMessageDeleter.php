<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/SitePrivateDataDeleter.php';
require_once 'Site/Site.php';

/**
 * Removes old contact messages which can contain personal information. As it
 * would be too hard to filter the message for personal information, it just
 * deletes the message altogether.
 *
 * @package   Site
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteContactMessageDeleter extends SitePrivateDataDeleter
{
	// {{{ public function run()

	public function run()
	{
		$this->app->debug("\n".Site::_('Contact Messages')."\n--------\n");

		$total = $this->getTotal();
		if ($total == 0) {
			$this->app->debug(
				Site::_('No expired contact messages found.')."\n");
		} else {
			$this->app->debug(sprintf(
				Site::_('Found %s contact messages for deletion...')."\n\n",
				$total));

			if (!$this->app->isDryRun()) {
				$delete_count = $this->deleteContactMessages();
				$this->app->debug(' '.Site::_('%s deleted.')."\n");
			} else {
				$this->app->debug(' '.
					Site::_('not deleting because dry-run is on')."\n");
			}

			$this->app->debug("\n".
				Site::_('Finished deleting expired contact messages.')."\n");
		}
	}

	// }}}
	// {{{ protected function deleteContactMessages()

	/**
	 * Deletes all expired contact messages
	 */
	protected function deletContactMessages()
	{
		$sql = sprintf('delete from ContactMessage %s',
			$this->getWhereClause());

		return SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getTotal()

	protected function getTotal()
	{
		$sql = sprintf('select count(id) from ContactMessage %s',
			$this->getWhereClause());

		return SwatDB::queryOne($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getExpiryDate()

	protected function getExpiryDate()
	{
		$unix_time =
			strtotime('-'.$this->app->config->expiry->contact_messages);

		$expiry_date = new SwatDate();
		$expiry_date->setTimestamp($unix_time);
		$expiry_date->toUTC();

		return $expiry_date;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$expiry_date = $this->getExpiryDate();
		$instance_id = $this->app->getInstanceId();

		// check expiry from sent_date instead of createdate so that we give a
		// year from when the contact message would have been received.
		$sql = 'where sent_date < %s
			and instance %s %s';

		$sql = sprintf($sql,
			$this->app->db->quote($expiry_date->getDate(), 'date'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		return $sql;
	}

	// }}}
}

?>
