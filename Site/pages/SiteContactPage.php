<?php

/**
 *
 * @package   Site
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteContactPage extends SiteDBEditPage
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/contact.xml';
	}

	// }}}
	// {{{ protected function isNew()

	protected function isNew(SwatForm $form)
	{
		// Treat all Contact forms as not new, so the default loading of
		// email addresses works.
		return false;
	}

	// }}}
	// {{{ protected function getContactMessageClassName()

	protected function getContactMessageClassName()
	{
		return SwatDBClassMap::get(SiteContactMessage::class);
	}

	// }}}
	// {{{ protected function getContactAddress()

	protected function getContactAddress()
	{
		return $this->app->config->email->contact_address;
	}

	// }}}
	// {{{ protected function getContactAddressLink()

	protected function getContactAddressLink()
	{
		$contact_address_link = new SwatHtmlTag('a');
		$contact_address_link->href = sprintf(
			'mailto:%s',
			$this->getContactAddress()
		);

		$contact_address_link->setContent($this->getContactAddress());

		return $contact_address_link;
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		// Don't bother with the Auth Token to prevent authentication error
		// messages as it is unnecessary here. This can happen when users keep
		// the contact page open in a separate tab and regenerate their session
		// id by switching from http to https.
		SwatForm::clearAuthenticationToken();

		$this->initSubject();
	}

	// }}}
	// {{{ protected function initSubject()

	protected function initSubject()
	{
		$subject = SiteApplication::initVar(
			'subject',
			null,
			SiteApplication::VAR_GET
		);

		if ($subject != '') {
			$class_name = $this->getContactMessageClassName();
			if (array_key_exists($subject, $class_name::getSubjects())) {
				$this->ui->getWidget('subject')->value = $subject;
			}
		}
	}

	// }}}

	// process phase
	// {{{ protected function saveData()

	protected function saveData(SwatForm $form)
	{
		$class_name = $this->getContactMessageClassName();
		$contact_message = new $class_name();
		$contact_message->setDatabase($this->app->db);

		$this->processMessage($contact_message);

		$contact_message->class_name = $class_name;
		$contact_message->spam = $this->isMessageSpam($contact_message);
		$contact_message->save();
	}

	// }}}
	// {{{ protected function processMessage()

	protected function processMessage(SiteContactMessage $message)
	{
		$message->email      = $this->ui->getWidget('email')->value;
		$message->subject    = $this->ui->getWidget('subject')->value;
		$message->message    = $this->ui->getWidget('message')->value;
		$message->instance   = $this->app->getInstance();
		$message->ip_address = $this->app->getRemoteIP(15);

		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$user_agent = $_SERVER['HTTP_USER_AGENT'];

			// Filter bad character encoding. If invalid, assume ISO-8859-1
			// encoding and convert to UTF-8.
			if (!SwatString::validateUtf8($user_agent)) {
				$user_agent = iconv('ISO-8859-1', 'UTF-8', $user_agent);
			}

			// Only save if the user-agent was successfully converted to UTF-8.
			if ($user_agent !== false) {
				// set max length based on database field length
				$user_agent = mb_substr($user_agent, 0, 255);
				$message->user_agent = $user_agent;
			}
		}

		$message->createdate = new SwatDate();
		$message->createdate->toUTC();
	}

	// }}}
	// {{{ protected function isMessageSpam()

	protected function isMessageSpam(SiteContactMessage $message)
	{
		return false;
	}

	// }}}
	// {{{ protected function getRollbackMessage()

	protected function getRollbackMessage(SwatForm $form)
	{
		$message = new SwatMessage(
			Site::_('An error has occurred. Your message was not sent.'),
			'system-error'
		);

		$message->secondary_content = sprintf(
			Site::_(
				'If this issue persists, or your message is time sensitive, '.
				'please send an email directly to %s.'
			),
			$this->getContactAddressLink()
		);

		$message->content_type = 'text/xml';

		return $message;
	}

	// }}}
	// {{{ protected function validate()

	protected function validate(SwatForm $form)
	{
		parent::validate($form);

		// Ensure a subject is set. Some robots try to submit the contact form
		// and omit HTTP POST data for the subject field. Due to Swat's
		// architecture, the widget will not raise its own validation error if
		// no POST data exists for the subject flydown. We check for a null
		// value here and explicitly add a validation message.
		$subject = $this->ui->getWidget('subject');
		if ($subject->value === null) {
			$message = new SwatMessage(
				Site::_('The <strong>%s<strong> field is required.')
			);

			$message->content_type = 'text/xml';
			$subject->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate(SwatForm $form)
	{
		if (!$this->app->session->isActive() ||
			count($this->app->messages) === 0) {
			$this->app->relocate($this->source.'/thankyou');
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildContent()

	protected function buildContent()
	{
		$this->layout->startCapture('content', $this->shouldPrependUi());
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$email_to = $this->ui->getWidget('email_to');
		$email_to->content_type = 'text/xml';
		$email_to->content = $this->getContactAddressLink();

		$class_name = $this->getContactMessageClassName();
		$subject_flydown = $this->ui->getWidget('subject');
		$subject_flydown->addOptionsByArray($class_name::getSubjects());
	}

	// }}}
	// {{{ protected function shouldPrependUi()

	protected function shouldPrependUi()
	{
		// Prepend UI content by default.
		return true;
	}

	// }}}
	// {{{ protected function load()

	protected function load(SwatForm $form)
	{
		parent::load($form);
		$this->loadDefaultEmailAddress();
	}

	// }}}
	// {{{ protected function loadDefaultEmailAddress()

	protected function loadDefaultEmailAddress()
	{
		if ($this->app->hasModule('SiteAccountSessionModule')) {
			$session = $this->app->getModule('SiteAccountSessionModule');
			if ($session->isLoggedIn()) {
				$this->ui->getWidget('email')->value = $session->account->email;
			}
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addBodyClass('contact-page');
		$this->layout->addHtmlHeadEntry(
			'packages/site/styles/site-contact-page.css'
		);
	}

	// }}}
}

?>
