<?php

require_once 'Site/pages/SiteEditPage.php';

/**
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteMailingSignupPage extends SiteEditPage
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/pages/mailing-signup.xml';
	}

	// }}}

	// process phase
	// {{{ protected function save()

	protected function save(SwatForm $form)
	{
		$list = $this->getList();
		$this->subscribe($list);
	}

	// }}}
	// {{{ abstract protected function getList()

	abstract protected function getList();

	// }}}
	// {{{ protected function save()

	protected function subscribe(SiteMailingList $list)
	{
		$email     = $this->getEmail();
		$info      = $this->getSubscriberInfo();
		$array_map = $this->getArrayMap();

		$list->subscribe($email, $info, $array_map);
	}

	// }}}
	// {{{ protected function getEmail()

	protected function getEmail()
	{
		return $this->ui->getWidget('email')->value;
	}

	// }}}
	// {{{ abstract protected function getSubscriberInfo();

	abstract protected function getSubscriberInfo();

	// }}}
	// {{{ protected function getArrayMap()

	protected function getArrayMap()
	{
		return array();
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
