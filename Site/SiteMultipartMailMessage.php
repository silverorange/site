<?php

require_once 'Site/SiteObject.php';
require_once 'Site/exceptions/SiteMailException.php';

require_once 'Mail.php';
require_once 'Mail/mime.php';

/**
 * A class for sending multipart html/txt emails
 *
 * @package   Site
 * @copyright 2004-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMultipartMailMessage extends SiteObject
{
	// {{{ public variables

	/**
	 * Email Subject 
	 *
	 * @var string
	 */
	public $subject = '';

	/**
	 * Recipient's Email Address
	 *
	 * @var string
	 */
	public $to_address = null;

	/**
	 * Recipient's Name
	 *
	 * @var string
	 */
	public $to_name = '';

	/**
	 * Sender's Email Address
	 *
	 * @var string
	 */
	public $from_address = null;

	/**
	 * Sender's Name
	 *
	 * @var string
	 */
	public $from_name = '';

	/**
	 * Sender's Reply-To Address
	 *
	 * @var string
	 */
	public $reply_to_address = null;

	/**
	 * Text body
	 *
	 * @var string
	 */
	public $text_body = '';

	/**
	 * HTML body
	 *
	 * @var string
	 */
	public $html_body = '';

	/**
	 * SMTP Server Address
	 *
	 * @var string
	 */
	public $smtp_server = null;

	// }}}
	// {{{ protected variables

	/**
	 * The application sending mail
	 *
	 * @var SiteApplication 
	 */
	protected $app = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new multipart mail message
	 *
	 * @param SiteApplication $app the application sending this mail message.
	 */
	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ public function send()

	/**
	 * Sends a multi-part email
	 */
	public function send()
	{
		$crlf = "\n";
		$mime = new Mail_mime($crlf);

		$mime->setTXTBody($this->text_body);
		$mime->setHTMLBody($this->html_body);

		$email_params = array();
		$email_params['host'] = $this->smtp_server;
		$mailer = Mail::factory('smtp', $email_params);

		if (PEAR::isError($mailer))
			throw new SiteMailException($mailer);

		$headers['From'] =
			sprintf('"%s" <%s>', $this->from_name, $this->from_address);

		if ($this->reply_to_address !== null)
			$headers['Reply-To'] = $this->reply_to_address;

		$headers['To'] =
			sprintf('"%s" <%s>', $this->to_name, $this->to_address);

		$headers['Subject'] = $this->subject;

		$mime_params = array();
		$mime_params['head_charset'] = 'UTF-8';
		$mime_params['text_charset'] = 'UTF-8';
		$mime_params['text_encoding'] = 'quoted-printable';
		$mime_params['html_charset'] = 'UTF-8';
		$mime_params['html_encoding'] = 'quoted-printable';
		$body = $mime->get($mime_params);
		$headers = $mime->headers($headers);

		$result = $mailer->Send($this->to_address, $headers, $body);

		if (PEAR::isError($result))
			throw new SiteMailException($result);
	}

	// }}}
}

?>
