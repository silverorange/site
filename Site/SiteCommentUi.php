<?php

require_once 'Swat/SwatUI.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/Site.php';
require_once 'Site/SiteViewFactory.php';
require_once 'Site/SiteCommentStatus.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Services/Akismet2.php';
require_once 'NateGoSearch/NateGoSearch.php';

/**
 * Handle the init, processing, and display of a comment UI
 *
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteCommentUi
{
	// {{{ class constants

	const THANK_YOU_ID = 'thank-you';

	// }}}
	// {{{ protected properties

	/**
	 * @var SiteApplication
	 */
	protected $app;

	/**
	 * @var string
	 */
	protected $source;

	/**
	 * @var SiteComment
	 */
	protected $comment;

	/**
	 * @var SiteCommentStatus
	 */
	protected $post;

	/**
	 * @var SwatUI
	 */
	protected $ui;

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/pages/comment-edit.xml';

	// }}}

	// init phase
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteCommentStatus $post,
		$source)
	{
		$this->post = $post;
		$this->app  = $app;
		$this->source  = $source;
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		$this->ui = new SwatUI();
		$this->ui->loadFromXml($this->ui_xml);
	}

	// }}}
	// {{{ protected function getComment()

	protected function getComment()
	{
		$class_name = SwatDBClassMap::get('SiteComment');
		return new $class_name();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		$form = $this->ui->getWidget('comment_edit_form');

		// wrap form processing in try/catch to catch bad input from spambots
		try {
			$form->process();
		} catch (SwatInvalidSerializedDataException $e) {
			$this->app->replacePage('httperror');
			$this->app->getPage()->setStatus(400);
			return;
		}

		$comment_status = $this->post->comment_status;
		if (($comment_status == SiteCommentStatus::OPEN ||
			$comment_status == SiteCommentStatus::MODERATED) &&
			$form->isProcessed() && !$form->hasMessage()) {

			$this->processComment();

			if ($this->ui->getWidget('post_button')->hasBeenClicked()) {
				$this->saveComment();

				switch ($this->post->comment_status) {
				case SiteCommentStatus::OPEN:
					$uri = $this->getThankYouUri().
						'#comment'.$this->comment->id;

					break;

				case SiteCommentStatus::MODERATED:
					$uri = $this->getThankYouUri().'#submit_comment';
					break;

				default:
					$uri = $this->source;
					break;
				}

				$this->app->relocate($uri);
			}
		}

	}

	// }}}
	// {{{ abstract protected function setCommentPost()

	abstract protected function setCommentPost(SiteComment $comment,
		SiteCommentStatus $post);

	// }}}
	// {{{ abstract protected function getPermalink()

	abstract protected function getPermalink(SiteComment $comment);

	// }}}
	// {{{ protected function processComment()

	protected function processComment()
	{
		$now = new SwatDate();
		$now->toUTC();

		$fullname = $this->ui->getWidget('fullname');
		$link     = $this->ui->getWidget('link');
		$email    = $this->ui->getWidget('email');
		$bodytext = $this->ui->getWidget('bodytext');

		if (isset($_SERVER['REMOTE_ADDR'])) {
			$ip_address = substr($_SERVER['REMOTE_ADDR'], 0, 15);
		} else {
			$ip_address = null;
		}

		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);
		} else {
			$user_agent = null;
		}

		$this->comment = $this->getComment();
		$this->comment->fullname   = $fullname->value;
		$this->comment->link       = $link->value;
		$this->comment->email      = $email->value;
		$this->comment->bodytext   = $bodytext->value;
		$this->comment->createdate = $now;
		$this->comment->ip_address = $ip_address;
		$this->comment->user_agent = $user_agent;

		switch ($this->post->comment_status) {
		case SiteCommentStatus::OPEN:
			$this->comment->status = SiteComment::STATUS_PUBLISHED;
			break;

		case SiteCommentStatus::MODERATED:
			$this->comment->status = SiteComment::STATUS_PENDING;
			break;
		}

		$this->setCommentPost($this->comment, $this->post);
	}

	// }}}
	// {{{ protected function getThankYouUri()

	protected function getThankYouUri()
	{
		return $this->source.'?'.self::THANK_YOU_ID;
	}

	// }}}
	// {{{ protected function saveComment()

	protected function saveComment()
	{
		if ($this->ui->getWidget('remember_me')->value) {
			$this->saveCommentCookie();
		} else {
			$this->deleteCommentCookie();
		}

		$this->comment->spam = $this->isCommentSpam($this->comment);
		$this->addCommentToPost($this->post, $this->comment);
		$this->post->save();
		$this->addToSearchQueue();

		// clear posts cache if comment is visible
		if (isset($this->app->memcache)) {
			if (!$this->comment->spam &&
				$this->comment->status === SiteComment::STATUS_PUBLISHED) {
				$this->clearCache();
			}
		}
	}

	// }}}
	// {{{ protected function addCommentToPost()

	protected function addCommentToPost(SiteCommentStatus $post,
		SiteComment $comment)
	{
		$post->comments->add($comment);
	}

	// }}}
	// {{{ protected function saveCommentCookie()

	protected function saveCommentCookie()
	{
		$fullname = $this->ui->getWidget('fullname')->value;
		$link     = $this->ui->getWidget('link')->value;
		$email    = $this->ui->getWidget('email')->value;

		$value = array(
			'fullname' => $fullname,
			'link'     => $link,
			'email'    => $email,
		);

		$this->app->cookie->setCookie('comment_credentials', $value);
	}

	// }}}
	// {{{ protected function deleteCommentCookie()

	protected function deleteCommentCookie()
	{
		$this->app->cookie->removeCookie('comment_credentials');
	}

	// }}}
	// {{{ protected function isCommentSpam()

	protected function isCommentSpam(SiteComment $comment)
	{
		$is_spam = false;

		if ($this->app->config->comment->akismet_key !== null) {
			$uri = $this->app->getBaseHref();
			try {
				$akismet = new Services_Akismet2($uri,
					$this->app->config->comment->akismet_key);

				$akismet_comment = new Services_Akismet2_Comment(
					array(
						'comment_author'       => $comment->fullname,
						'comment_author_email' => $comment->email,
						'comment_author_url'   => $comment->link,
						'comment_content'      => $comment->bodytext,
						'permalink'            => $this->getPermalink($comment),
					)
				);

				$is_spam = $akismet->isSpam($akismet_comment, true);
			} catch (Exception $e) {
			}
		}

		return $is_spam;
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
	}

	// }}}
	// {{{ protected function clearCache()

	protected function clearCache()
	{
	}

	// }}}

	// build phase
	// {{{ public function display()

	public function display()
	{
		$this->build();
		$this->buildCommentPreview();

		$this->displayCommentUi();
	}

	// }}}
	// {{{ protected function build()

	protected function build()
	{
		$ui              = $this->ui;
		$form            = $ui->getWidget('comment_edit_form');
		$frame           = $ui->getWidget('comment_edit_frame');
		$frame->subtitle = $this->post->getTitle();
		$show_thank_you  = array_key_exists(self::THANK_YOU_ID, $_GET);

		switch ($this->getCommentStatus()) {
		case SiteCommentStatus::OPEN:
		case SiteCommentStatus::MODERATED:
			$form->action = $this->source.'#submit_comment';
			try {
				if (isset($this->app->cookie->comment_credentials)) {
					$values = $this->app->cookie->comment_credentials;
					$ui->getWidget('fullname')->value    = $values['fullname'];
					$ui->getWidget('link')->value        = $values['link'];
					$ui->getWidget('email')->value       = $values['email'];
					$ui->getWidget('remember_me')->value = true;
				}
			} catch (SiteCookieException $e) {
				// ignore cookie errors, but delete the bad cookie
				$this->app->cookie->removeCookie('comment_credentials');
			}
			break;

		case SiteCommentStatus::LOCKED:
			$form->visible = false;
			$message = new SwatMessage($this->getMessage('locked'));
			$message->secondary_content = $this->getMessage('locked-subtitle');

			$ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);

			break;

		case SiteCommentStatus::CLOSED:
			$ui->getRoot()->visible = false;
			break;
		}

		if ($show_thank_you) {
			switch ($this->post->comment_status) {
			case SiteCommentStatus::OPEN:
				$message = new SwatMessage($this->getMessage('published'));
				$this->ui->getWidget('message_display')->add($message,
					SwatMessageDisplay::DISMISS_OFF);

				break;

			case SiteCommentStatus::MODERATED:
				$message = new SwatMessage($this->getMessage('moderated'));
				$message->secondary_content =
					$this->getMessage('moderated-subtitle');

				$this->ui->getWidget('message_display')->add($message,
					SwatMessageDisplay::DISMISS_OFF);

				break;
			}
		}
	}

	// }}}
	// {{{ protected function buildCommentPreview()

	protected function buildCommentPreview()
	{
		if ($this->comment instanceof SiteComment &&
			$this->ui->getWidget('preview_button')->hasBeenClicked()) {

			$button_tag = new SwatHtmlTag('input');
			$button_tag->type = 'submit';
			$button_tag->name = 'post_button';
			$button_tag->value = Site::_('Post');

			$message = new SwatMessage($this->getMessage('preview-message'));
			$message->secondary_content = sprintf(
				$this->getMessage('preview-message-subtitle'), $button_tag);

			$message->content_type = 'text/xml';

			$message_display =
				$this->ui->getWidget('preview_message_display');

			$message_display->add($message, SwatMessageDisplay::DISMISS_OFF);

			ob_start();

			$view = SiteViewFactory::get($this->app, 'comment');
			$view->display($this->comment);

			$comment_preview = $this->ui->getWidget('comment_preview');
			$comment_preview->content = ob_get_clean();
			$comment_preview->content_type = 'text/xml';

			$container = $this->ui->getWidget(
				'comment_preview_container');

			$container->visible = true;
		}
	}

	// }}}
	// {{{ protected function getMessage()

	protected function getMessage($shortname)
	{
		switch ($shortname) {
		case 'preview-message' :
			return Site::_('Your comment has not yet been published.');

		case 'preview-message-subtitle' :
			return Site::_('Review your comment and press the <em>Post</em> '.
				'button when it’s ready to publish. %s');

		case 'locked' :
			return Site::_('Comments are locked');

		case 'locked-subtitle' :
			return Site::_('No new comments may be posted for this article.');

		case 'published' :
			return Site::_('Your comment has been published.');

		case 'moderated' :
			return Site::_('Your comment has been submitted.');

		case 'moderated-subtitle' :
			return Site::_('Your comment will be published after being '.
				'approved by the site moderator.');

		default :
			return null;
		}
	}

	// }}}
	// {{{ protected function displayCommentUi()

	protected function displayCommentUi()
	{
		// Comment form submits to the top of the comment form if there are
		// error messages or if the new comment is not immediately visible.
		// Otherwise the comment form submits to the new comment.
		$comment_status = $this->post->comment_status;
		if ($this->ui->getWidget('comment_edit_form')->hasMessage() ||
			$comment_status == SiteCommentStatus::MODERATED ||
			$comment_status == SiteCommentStatus::LOCKED ||
			$this->ui->getWidget('preview_button')->hasBeenClicked()) {
			$this->displaySubmitComment();
		}

		$this->ui->display();
	}

	// }}}
	// {{{ protected function displaySubmitComment()

	protected function displaySubmitComment()
	{
		echo '<div id="submit_comment"></div>';
	}

	// }}}
	// {{{ protected function getCommentStatus()

	protected function getCommentStatus()
	{
		return $this->post->getCommentStatus();
	}

	// }}}

	// finalize phase
	// {{{ public function getHtmlHeadEntrySet()

	public function getHtmlHeadEntrySet()
	{
		return $this->ui->getRoot()->getHtmlHeadEntrySet();
	} 

	// }}}
}

?>
