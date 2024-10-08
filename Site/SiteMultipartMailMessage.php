<?php

use Pelago\Emogrifier;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Multipart text/html email message.
 *
 * @copyright 2006-2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMultipartMailMessage extends SiteObject
{
    /**
     * Email subject.
     *
     * @var string
     */
    public $subject = '';

    /**
     * Recipient's email address.
     *
     * @var string
     */
    public $to_address;

    /**
     * Recipient's name.
     *
     * @var string
     */
    public $to_name = '';

    /**
     * Sender's email address.
     *
     * @var string
     */
    public $from_address;

    /**
     * Sender's name.
     *
     * @var string
     */
    public $from_name = '';

    /**
     * Addresses to which to carbon-copy (CC) this mail message.
     *
     * @var array
     */
    public $cc_list = [];

    /**
     * Addresses to which to blind-carbon-copy (BCC) this mail message.
     *
     * @var array
     */
    public $bcc_list = [];

    /**
     * Sender's reply-to address.
     *
     * @var string
     */
    public $reply_to_address;

    /**
     * Return path for bounces.
     *
     * The return path should be set to an address owned by the site or
     * service sending mail to appear authentic in SPF checks.
     *
     * @var string
     */
    public $return_path;

    /**
     * Sender of this email.
     *
     * Can be use for user-initiated emails sent by a site or service. The
     * 'sender' can be the site or service and the 'from' can be the user.
     * This allows for sending emails on behalf of a user and passing SPF
     * checks.
     *
     * @var string
     */
    public $sender;

    /**
     * Sender's name.
     *
     * Name for the sender when using the sender header for user-initiated
     * emails sent by a site or service.
     *
     * @var string
     */
    public $sender_name;

    /**
     * Text body.
     *
     * @var string
     */
    public $text_body = '';

    /**
     * HTML body.
     *
     * @var string
     */
    public $html_body = '';

    /**
     * SMTP server address.
     *
     * @var string
     */
    public $smtp_server;

    /**
     * SMTP port.
     *
     * @var int
     */
    public $smtp_port;

    /**
     * SMTP username.
     *
     * @var string
     */
    public $smtp_username;

    /**
     * SMTP password.
     *
     * @var string
     */
    public $smtp_password;

    /**
     * Files to attach to this mail message.
     *
     * @var array
     */
    public $attachments = [];

    /**
     * The application sending mail.
     *
     * @var SiteApplication
     */
    protected $app;

    /**
     * Date of the email.
     *
     * @var SwatDate
     */
    protected $date;

    /**
     * Data to include with this mail message as attachments.
     *
     * @var array
     *
     * @see SiteMultipartMailMessage::addAttachmentFromString()
     */
    protected $string_attachments = [];

    /**
     * Creates a new multipart mail message.
     *
     * @param SiteApplication $app the application sending this mail message
     */
    public function __construct(SiteApplication $app)
    {
        $this->app = $app;

        $this->date = new SwatDate();
        $this->date->toUTC();
    }

    /**
     * Sends a multi-part email.
     */
    public function send()
    {
        try {
            $email = (new Email())
                ->subject($this->subject)
                ->from(new Address(
                    $this->from_address,
                    $this->from_name
                ))
                ->text($this->text_body);

            if ($this->html_body != '') {
                $email->html($this->convertCssToInlineStyles($this->html_body));
            }

            // don't send CC emails if test-address is specified
            if ($this->app->config->email->test_address == '') {
                $email
                    ->cc(...$this->getCcList())
                    ->bcc(...$this->getBccList());
            }

            // file attachments
            foreach ($this->attachments as $attachment) {
                $email->attachFromPath($attachment);
            }

            // attachments with metadata
            foreach ($this->string_attachments as $attachment) {
                $email->attach(
                    $attachment['data'],
                    $attachment['filename'],
                    $attachment['content_type'],
                );
            }

            $dsn = 'smtp://';

            if ($this->smtp_username != '') {
                $dsn .= urlencode($this->smtp_username);
                if ($this->smtp_password != '') {
                    $dsn .= ':' . urlencode($this->smtp_password);
                }
                $dsn .= '@';
            }

            $dsn .= urlencode($this->smtp_server);

            if ($this->smtp_port != '') {
                $dsn .= ':' . $this->smtp_port;
            } else {
                // Default port of 25
                $dsn .= ':25';
            }

            $dsn .= '?verify_peer=0';

            $mailer = new Mailer(Transport::fromDsn($dsn));

            if ($this->return_path != '') {
                $email->returnPath($this->return_path);
            }

            $email->date(new DateTime($this->date->getRFC2822()));

            if ($this->app->config->email->test_address == '') {
                $email->to(new Address(
                    $this->to_address,
                    $this->to_name
                ));
            } else {
                $email->to(new Address(
                    $this->app->config->email->test_address,
                    $this->to_name
                ));
            }

            if ($this->reply_to_address != '') {
                $email->replyTo($this->reply_to_address);
            }

            if ($this->sender != '') {
                $email->sender(new Address(
                    $this->sender,
                    $this->sender_name
                ));
            }

            $mailer->send($email);

            if ($this->app->config->email->log) {
                $this->logMessage();
            }
        } catch (Throwable $e) {
            throw new SiteMailException($e);
        }
    }

    /**
     * Adds an email address to the bcc list.
     *
     * @param string $email the email address to add
     */
    public function addCc($email)
    {
        $this->cc_list[] = $email;
    }

    /**
     * Adds an email address to the bcc list.
     *
     * @param string $email the email address to add
     */
    public function addBcc($email)
    {
        $this->bcc_list[] = $email;
    }

    /**
     * Gets an array of email addresses for CC.
     *
     * @return array $email addresses for CC
     */
    public function getCcList()
    {
        $list = [];
        foreach ($this->cc_list as $email) {
            if (trim($email) != '') {
                $list[] = trim($email);
            }
        }

        return $list;
    }

    /**
     * Gets an array of email addresses for BCC.
     *
     * @return array $email addresses for BCC
     */
    public function getBccList()
    {
        $list = [];
        foreach ($this->bcc_list as $email) {
            if (trim($email) != '') {
                $list[] = trim($email);
            }
        }

        return $list;
    }

    public function addAttachmentFromString(
        $data,
        $filename = null,
        $content_type = null
    ) {
        $this->string_attachments[] = ['data' => $data, 'filename' => $filename, 'content_type' => $content_type];
    }

    protected function getAddressHeader($address, $name = '')
    {
        $header = ($name != '') ?
            '"%2$s" <%1$s>' :
            '%1$s';

        return sprintf($header, $address, $name);
    }

    protected function getRecipients()
    {
        if ($this->app->config->email->test_address == '') {
            $recipients = array_merge(
                [$this->to_address],
                $this->getCcList(),
                $this->getBccList()
            );
        } else {
            $recipients = [$this->app->config->email->test_address];
        }

        return implode(', ', $recipients);
    }

    protected function logMessage()
    {
        // Log details that would be useful for statistics.
        $sql = 'insert into SiteEmailLog
			(createdate, instance, type, attachment_count, attachment_size,
			to_address, from_address, recipient_type) values %s';

        $values_sql = '(%s, %s, %s, %s, %s, %%s, %s, %%s)';

        $attachment_size = 0;

        // file attachment support
        foreach ($this->attachments as $attachment) {
            $attachment_size += filesize($attachment);
        }

        // string attachments with metadata
        foreach ($this->string_attachments as $attachment) {
            $attachment_size += mb_strlen($attachment['data'], '8bit');
        }

        $attachment_count = count($this->attachments) +
            count($this->string_attachments);

        $values_sql = sprintf(
            $values_sql,
            $this->app->db->quote($this->date, 'date'),
            $this->app->db->quote($this->app->getInstanceId(), 'integer'),
            $this->app->db->quote(static::class, 'text'),
            $this->app->db->quote($attachment_count, 'integer'),
            $this->app->db->quote($attachment_size, 'integer'),
            $this->app->db->quote($this->from_address, 'text'),
        );

        $values = [];
        $values[] = sprintf(
            $values_sql,
            $this->app->db->quote($this->to_address, 'text'),
            $this->app->db->quote('to', 'text')
        );

        foreach ($this->getCcList() as $recipient) {
            $values[] = sprintf(
                $values_sql,
                $this->app->db->quote($recipient, 'text'),
                $this->app->db->quote('cc', 'text')
            );
        }

        foreach ($this->getBccList() as $recipient) {
            $values[] = sprintf(
                $values_sql,
                $this->app->db->quote($recipient, 'text'),
                $this->app->db->quote('bcc', 'text')
            );
        }

        $sql = sprintf($sql, implode(',', $values));

        SwatDB::exec($this->app->db, $sql);
    }

    /**
     * Attempt to convert css to inline styles.
     *
     * @param mixed $html
     */
    protected function convertCssToInlineStyles($html)
    {
        // Emogrifier is optional. If not included, just return the regular
        // HTML with inline CSS
        if ($html != '' && class_exists('Pelago\Emogrifier')) {
            $reset_errors = libxml_use_internal_errors(true);
            $emogrifier = new Emogrifier($html);
            $inlined = $emogrifier->emogrify();

            // log errors so we can find XML defects
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($reset_errors);
            if (count($errors) > 0) {
                $this->logLibXmlErrors($html, $errors);
            }
        } else {
            $inlined = $html;
        }

        return $inlined;
    }

    /**
     * Attempt to convert css to inline styles.
     *
     * @param mixed $html
     */
    protected function logLibXmlErrors($html, array $errors)
    {
        $error_message = '';
        foreach ($errors as $error) {
            $error_message .= sprintf(
                "Message: %s\n" .
                "Code: %s\n" .
                'Line: %s, Column: %s',
                $error->message,
                $error->code,
                $error->line,
                $error->column
            );
        }

        $error_message .= "\n\nInput XML\n\n:" . $html;

        $exception = new SwatException($error_message);
        $exception->processAndContinue();
    }
}
