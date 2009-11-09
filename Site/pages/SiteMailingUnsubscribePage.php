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
		$unsubscribed = $list->unsubscribe($email);

		switch ($unsubscribed) {
		case SiteMailingList::NOT_FOUND:
			$message = new SwatMessage(Site::_('Thank you. Your email address '.
				'was never subscribed to our newsletter.'),
				'notice');

			$message->secondary_content =
				Site::_('You will not receive any mailings to this address.');

			break;

		case SiteMailingList::NOT_SUBSCRIBED:
			$message = new SwatMessage(Site::_('Thank you. Your email address '.
				'has already been unsubscribed from our newsletter.'),
				'notice');

			$message->secondary_content =
				Site::_('You will not receive any mailings to this address.');

			break;

		case SiteMailingList::FAILURE:
			$message = new SwatMessage(Site::_('Sorry, there was an issue '.
				'unsubscribing from the list.'),
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

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$email = SiteApplication::initVar('email');
		if (strlen($email) > 0) {
			$this->ui->getWidget('email')->value = $email;
		}
	}

	// }}}

}

?>
