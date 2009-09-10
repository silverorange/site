<?php

require_once 'Swat/SwatUI.php';
require_once 'Swat/SwatString.php';
require_once 'Site/SiteMultipartMailMessage.php';
require_once 'Site/pages/SiteEditPage.php';

/**
 *
 * @package   Site
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteContactPage extends SiteEditPage
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Site/pages/contact.xml';
	}

	// }}}
	// {{{ protected function getSubjects()

	protected function getSubjects()
	{
		$subjects = array(
			'general'  => Site::_('General Question'),
			'website'  => Site::_('Website'),
			'privacy'  => Site::_('Privacy'),
		);

		return $subjects;
	}

	// }}}

	// process phase
	// {{{ protected function save()

	protected function save(SwatForm $form)
	{
		$this->sendEmail();
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate(SwatForm $form)
	{
		$this->app->relocate($this->source.'/thankyou');
	}

	// }}}
	// {{{ protected function sendEmail()

	protected function sendEmail()
	{
		$message = $this->getMessage();

		try {
			$message->send();
		} catch (SiteMailException $e) {
			$e->process(false);
		}
	}

	// }}}
	// {{{ protected function getMessage()

	protected function getMessage()
	{
		$message = new SiteMultipartMailMessage($this->app);

		$message->smtp_server      = $this->app->config->email->smtp_server;
		$message->from_address     = $this->app->config->email->website_address;
		$message->from_name        = $this->getFromName();
		$message->reply_to_address = $this->ui->getWidget('email')->value;
		$message->to_address       = $this->app->config->email->contact_address;

		$message->subject   = $this->getSubject();
		$message->text_body = $this->getTextBody();
		$message->text_body.= $this->browserInfo();

		return $message;
	}

	// }}}
	// {{{ protected function getSubject()

	protected function getSubject()
	{
		$subject_index = $this->ui->getWidget('subject')->value;
		$subjects = $this->getSubjects();
		$subject = sprintf('%s (%s)',
			$subjects[$subject_index],
			$this->ui->getWidget('email')->value);

		return $subject;
	}

	// }}}
	// {{{ protected function getTextBody()

	protected function getTextBody()
	{
		$text_body = sprintf(Site::_('Email From: %s'),
			$this->ui->getWidget('email')->value)."\n\n";

		$text_body.= $this->ui->getWidget('message')->value;

		return $text_body;
	}

	// }}}
	// {{{ protected function browserInfo()

	protected function browserInfo()
	{
		$info = "\n\n-------------------------\n";
		$info.= "User Information\n";

		if (isset($_SERVER['HTTP_USER_AGENT']))
			$info.= $_SERVER['HTTP_USER_AGENT'];
		else
			$info.= 'Not available';

		return $info;
	}

	// }}}
	// {{{ protected function getFromAddress()

	protected function getFromName()
	{
		$user_address = sprintf(Site::_('%s via %s'),
			$this->ui->getWidget('email')->value,
			$this->getSiteTitle());

		return $user_address;
	}

	// }}}
	// {{{ protected function getSiteTitle()

	protected function getSiteTitle()
	{
		return $this->app->config->site->title;
	}

	// }}}

	// build phase
	// {{{ protected function buildContent()

	protected function buildContent()
	{
		$this->layout->startCapture('content', true);
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
		$email_to->content = sprintf('<a href="mailto:%1$s">%1$s</a>',
			$this->app->config->email->contact_address);

		$subject_flydown = $this->ui->getWidget('subject');
		$subject_flydown->addOptionsByArray($this->getSubjects());
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/site/styles/site-contact-page.css',
			Site::PACKAGE_ID));
	}

	// }}}
}

?>
