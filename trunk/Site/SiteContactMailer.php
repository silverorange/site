<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteMultipleInstanceModule.php';
require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteMultipartMailMessage.php';
require_once 'Site/dataobjects/SiteContactMessageWrapper.php';

/**
 * Application to send pending contact message emails
 *
 * These are messages from users. They are submitted through the website and
 * are addressed to support staff for the website.
 *
 * @package   Site
 * @copyright 2010-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteContactMailer extends SiteCommandLineApplication
{
	// {{{ public properties

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

	// }}}
	// {{{ protected properties

	/**
	 * @var string
	 * @sse SiteContactMailer::setDebugDomain()
	 */
	protected $debug_domain = null;

	/**
	 * @var string
	 * @sse SiteContactMailer::setBaseClassName()
	 */
	protected $base_class_name = null;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		parent::__construct($id, $filename, $title, $documentation);

		$instance = new SiteCommandLineArgument(array('-i', '--instance'),
			'setInstance', 'Required. Sets the site instance for which to '.
			'run this application.');

		$instance->addParameter('string',
			'instance name must be specified.');

		$this->addCommandLineArgument($instance);

		$debug_domain = new SiteCommandLineArgument(
			array('-d', '--debug-domain'),
			'setDebugDomain',
			Site::_('Sets this mailer to debug mode. When set, only '.
				'messages with email addresses ending in the specified '.
				'domain are sent, and the messages are not marked as sent '.
				'so they may be sent multiple times.'));

		$debug_domain->addParameter(
			'string',
			Site::_('--debug-domain expects a valid domain.'));

		$this->addCommandLineArgument($debug_domain);

	}

	// }}}
	// {{{ public function setInstance()

	public function setInstance($shortname)
	{
		putenv(sprintf('instance=%s', $shortname));
		$this->instance->init();
		$this->config->init();
	}

	// }}}
	// {{{ public function setBaseClassName()

	public function setBaseClassName($base_class_name)
	{
		$this->base_class_name = $base_class_name;
	}

	// }}}
	// {{{ public function getBaseClassName()

	public function getBaseClassName()
	{
		if ($this->base_class_name === null) {
			$this->base_class_name = 'SiteContactMessage';
		}

		return $this->base_class_name;
	}

	// }}}
	// {{{ public function getClassName()

	public function getClassName()
	{
		return SwatDBClassMap::get($this->getBaseClassName());
	}

	// }}}
	// {{{ public function setDebugDomain()

	/**
	 * Sets the debug domain for this mailer
	 *
	 * When set, only messages with email addresses ending in this domain are
	 * sent, and the messages are not marked as sent so they may be sent
	 * multiple times.
	 *
	 * @param string $domain the debug domain to use.
	 */
	public function setDebugDomain($domain)
	{
		$this->debug_domain = $domain;
	}

	// }}}
	// {{{ public function run()

	public function run()
	{
		parent::run();
		$this->sendMessages();
	}

	// }}}
	// {{{ protected function sendMessages()

	protected function sendMessages()
	{
		$messages = $this->getPendingMessages();

		$this->debug(
			sprintf(
				Site::ngettext(
					"Got %s pending message.\n\n",
					"Got %s pending messages.\n\n",
					count($messages)
				),
				count($messages)
			),
			true
		);

		foreach ($messages as $message) {

			$this->debug(
				Site::_(
					sprintf(
						" => sending contact email from %s ... ",
						$message->email
					)
				)
			);

			$email = $this->getMailMessage($message);

			try {
				$email->send();
				$message->sent_date = new SwatDate();
				$message->sent_date->toUTC();
				$this->debug(Site::_("sent\n"));
			} catch (SiteMailException $e) {
				$message->error_date = new SwatDate();
				$message->error_date->toUTC();
				$this->debug(Site::_("failed to send\n"));
			}

			if ($this->debug_domain === null) {
				$message->save();
			}

		}

		$this->debug(Site::_("\nAll done.\n"), true);
	}

	// }}}
	// {{{ protected function getPendingMessages()

	protected function getPendingMessages()
	{
		$instance_id = $this->getInstanceId();

		$sql = sprintf('select * from ContactMessage
			where sent_date is null and error_date is null and
				instance %s %s
				and spam = %s',
			SwatDB::equalityOperator($instance_id),
			$this->db->quote($instance_id, 'integer'),
			$this->db->quote(false, 'boolean'));

		if ($this->getBaseClassName() == 'SiteContactMessage') {
			$class_name_sql = ' and (class_name = %s or class_name is null)';
		} else {
			$class_name_sql = ' and class_name = %s';
		}

		$sql.= sprintf(
			$class_name_sql,
			$this->db->quote($this->getClassName(), 'text')
		);

		if ($this->debug_domain !== null) {
			$sql.= sprintf(
				' and email like %s',
				$this->db->quote('%'.$this->debug_domain, 'text'));
		}

		$sql.= ' order by createdate asc';

		return SwatDB::query($this->db, $sql, $this->getWrapper());
	}

	// }}}
	// {{{ protected function getWrapper()

	protected function getWrapper()
	{
		return SwatDBClassMap::get('SiteContactMessageWrapper');
	}

	// }}}

	// building mail message
	// {{{ protected function getMailMessage()

	protected function getMailMessage(SiteContactMessage $contact_message)
	{
		$message = new SiteMultipartMailMessage($this);

		$message->smtp_server      = $this->config->email->smtp_server;
		$message->from_address     = $this->config->email->website_address;
		$message->from_name        = $this->getFromName($contact_message);
		$message->reply_to_address = $contact_message->email;
		$message->to_address       = $this->getToAddress($contact_message);
		$message->cc_list          = $this->getCcList($contact_message);
		$message->bcc_list         = $this->getBccList($contact_message);

		$message->subject   = $this->getSubject($contact_message);
		$message->text_body = $this->getTextBody($contact_message);
		$message->text_body.= $this->getTextBrowserInfo($contact_message);

		return $message;
	}

	// }}}
	// {{{ protected function getFromName()

	protected function getFromName(SiteContactMessage $contact_message)
	{
		$from_name = sprintf(Site::_('%s via %s'),
			$contact_message->email,
			$this->getSiteTitle());

		return $from_name;
	}

	// }}}
	// {{{ protected function getToAddress()

	protected function getToAddress(SiteContactMessage $message)
	{
		return $this->config->email->contact_address;
	}

	// }}}
	// {{{ protected function getCcList()

	protected function getCcList(SiteContactMessage $message)
	{
		$list = array();

		if ($this->config->email->contact_cc_list != '') {
			$list = explode(';', $this->config->email->contact_cc_list);
		}

		return $list;
	}

	// }}}
	// {{{ protected function getBccList()

	protected function getBccList(SiteContactMessage $message)
	{
		$list = array();

		if ($this->config->email->contact_bcc_list != '') {
			$list = explode(';', $this->config->email->contact_bcc_list);
		}

		return $list;
	}

	// }}}
	// {{{ protected function getSubject()

	protected function getSubject(SiteContactMessage $message)
	{
		// Dynamic static call to get subjects. This will be more straight-
		// forward in PHP 5.3.
		$class_name = $this->getClassName();
		$subjects = call_user_func(array($class_name, 'getSubjects'));

		if (array_key_exists($message->subject, $subjects)) {
			$subject = sprintf('%s (%s)',
				$subjects[$message->subject],
				$message->email);
		} else {
			$subject = sprintf(
				Site::_('General Message (%s)'),
				$message->email);
		}


		return $subject;
	}

	// }}}
	// {{{ protected function getTextBrowserInfo()

	protected function getTextBrowserInfo(SiteContactMessage $contact_message)
	{
		$info = "\n\n-------------------------\n";
		$info.= "User Information\n";

		if (isset($contact_message->user_agent)) {
			$info.= $contact_message->user_agent;
		} else {
			$info.= Site::_('Not available');
		}

		return $info;
	}

	// }}}
	// {{{ protected function getTextBody()

	protected function getTextBody(SiteContactMessage $contact_message)
	{
		$text_body = sprintf(
			Site::_('Email From: %s'),
			$contact_message->email);

		$text_body.= "\n\n";

		$contact_message->createdate->convertTZ($this->default_time_zone);
		$text_body.= $contact_message->createdate->formatLikeIntl(
			Site::_('EEEE, MMMM d, YYYY \'at\' h:mm a zzz'));

		$text_body.= "\n\n";
		$text_body.= $contact_message->message;

		return $text_body;
	}

	// }}}
	// {{{ protected function getSiteTitle()

	protected function getSiteTitle()
	{
		return $this->config->site->title;
	}

	// }}}

	// boilerplate
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this search indexer
	 *
	 * @return array the list of modules to load for this application.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'SiteConfigModule',
			'database' => 'SiteDatabaseModule',
			'instance' => 'SiteMultipleInstanceModule',
		);
	}

	// }}}
	// {{{ protected function configure()

	/**
	 * Configures modules of this application before they are initialized
	 *
	 * @param SiteConfigModule $config the config module of this application to
	 *                                  use for configuration other modules.
	 */
	protected function configure(SiteConfigModule $config)
	{
		parent::configure($config);
		$this->database->dsn = $config->database->dsn;
	}

	// }}}
}

?>
