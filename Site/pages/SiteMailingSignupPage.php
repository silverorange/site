<?php

require_once 'Site/pages/SiteEditPage.php';

/**
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteMailingSignupPage extends SiteEditPage
{
	// {{{ protected properties

	protected $send_welcome = true;

	// }}}
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
	// {{{ protected function subscribe()

	protected function subscribe(SiteMailingList $list)
	{
		$email     = $this->getEmail();
		$info      = $this->getSubscriberInfo();
		$array_map = $this->getArrayMap();

		$subscribed = $list->subscribe($email, $info, $this->send_welcome,
			$array_map);

		switch ($subscribed) {
		case SiteMailingList::INVALID:
			$message = new SwatMessage(Site::_('Sorry, the email address '.
				'you entered is not a valid email address.'),
				'error');

			break;

		case SiteMailingList::FAILURE:
			$message = new SwatMessage(Site::_('Sorry, there was an issue '.
				'subscribing you to the list.'),
				'error');

			$message->content_type = 'text/xml';
			$message->secondary_content = sprintf(Site::_('This can usually '.
				'be resolved by trying again later. If the issue persists '.
				'please <a href="%s">contact us</a>.'),
				$this->getContactUsLink());

			$message->content_type = 'txt/xhtml';
			break;

		default:
			$message = null;
		}

		if ($message instanceof SwatMessage) {
			$this->ui->getWidget('message_display')->add($message);
		}
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
		if ($this->ui->getWidget('message_display')->getMessageCount() == 0) {
			$this->app->relocate($this->source.'/thankyou');
		}
	}

	// }}}
	// {{{ protected function getContactUsLink()

	protected function getContactUsLink()
	{
		return 'about/contact';
	}

	// }}}
}

?>
