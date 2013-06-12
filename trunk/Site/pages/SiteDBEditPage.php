<?php

require_once 'SwatDB/exceptions/SwatDBException.php';
require_once 'Site/pages/SiteEditPage.php';

/**
 * Base class for database edit pages
 *
 * @package   Site
 * @copyright 2008-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
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
			if ($this->app->hasModule('SiteMessagesModule')) {
				$messages = $this->app->getModule('SiteMessagesModule');
				$messages->add($this->getRollbackMessage($form));
			}
			$transaction->rollback();
			$this->handleDBException($e);
		} catch (Exception $e) {
			$this->handleException($transaction, $e);
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
			Site::_('An error has occurred. The item was not saved.'),
			'system-error');

		return $message;
	}

	// }}}
	// {{{ protected function handleDBException()

	protected function handleDBException(SwatDBException $e)
	{
	}

	// }}}
	// {{{ protected function handleException()

	protected function handleException(SwatDBTransaction $transaction,
		Exception $e)
	{
		throw $e;
	}

	// }}}
}

?>
