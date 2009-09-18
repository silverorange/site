<?php

require_once 'Site/pages/SiteEditPage.php';

/**
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteMailingUnsubscribePage extends SiteEditPage
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/pages/mailing-unsubscribe.xml';
	}

	// }}}

	// process phase
	// {{{ protected function save()

	protected function save(SwatForm $form)
	{
		$list = $this->getList();
		$this->unsubscribe($list);
	}

	// }}}
	// {{{ abstract protected function getList()

	abstract protected function getList();

	// }}}
	// {{{ protected function save()

	protected function unsubscribe(SiteMailingList $list)
	{
		$email = $this->getEmail();
		$list->unsubscribe($email);
	}

	// }}}
	// {{{ protected function getEmail()

	protected function getEmail()
	{
		return $this->ui->getWidget('email')->value;
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate(SwatForm $form)
	{
		$this->app->relocate($this->source.'/thankyou');
	}

	// }}}
}

?>
