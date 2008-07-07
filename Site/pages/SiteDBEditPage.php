<?php

require_once 'SwatDB/exceptions/SwatDBException.php';
require_once 'Site/pages/SiteEditPage.php';

/**
 * Base class for database edit pages
 *
 * @package   Site
 * @copyright 2008 silverorange
 */
abstract class SiteDBEditPage extends SiteEditPage
{
	// process phase
	// {{{ protected function save()

	protected function save(SwatForm $form)
	{
		$transaction = new SwatDBTransaction($this->app->db);
		try {
			$this->saveData($form);
			$transaction->commit();
		} catch (SwatDBException $e) {
			$this->app->messages->add($this->getRollbackMessage($form));
			$transaction->rollback();
			throw $e;
		}
	}

	// }}}
	// {{{ abstract protected function saveData()

	abstract protected function saveData(SwatForm $form);

	// }}}
	// {{{ protected function getRollbackMessage()

	protected function getRollbackMessage(SwatForm $form)
	{
		$message = new SwatMessage(
			'An error has occurred. The item was not saved.',
			SwatMessage::SYSTEM_ERROR);

		return $message;
	}

	// }}}
}

?>
