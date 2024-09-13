<?php

/**
 * Page for adding a comment asynchronously via a JSON request
 *
 * @package   Site
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteCommentAddPage extends SitePageDecorator
{


	/**
	 * @var SiteCommentable
	 */
	protected $item;

	/**
	 * @var SiteComment
	 */
	protected $comment;

	/**
	 * @var array
	 */
	protected $response = [];




	protected function createLayout()
	{
		return new SiteLayout($this->app, SiteJSONTemplate::class);
	}



	// init phase


	public function init()
	{
		parent::init();
		$this->initItem();
		$this->initComment();
	}




	abstract protected function initItem();




	protected function initComment()
	{
		$class_name = $this->getCommentClassName();
		$this->comment = new $class_name();
		$this->comment->setDatabase($this->app->db);
	}




	protected function getCommentClassName()
	{
		return SwatDBClassMap::get(SiteComment::class);
	}



	// process phase


	public function process()
	{
		parent::process();

		try {
			match ($this->getItemCommentStatus()) {
				SiteCommentStatus::OPEN, SiteCommentStatus::MODERATED => $this->processComment(),
				default => throw new SiteCommentJSONException(
					Site::_('Commenting is not allowed for this item.')
				),
			};
		} catch (Throwable $e) {
			if (!($e instanceof SiteCommentJSONException)) {
				$e->processAndContinue();
			}
			$this->handleException($e);
		}
	}




	protected function processComment()
	{
		$this->updateComment();
		$this->saveComment();
	}




	protected function updateComment()
	{
		$now = new SwatDate();
		$now->toUTC();

		switch ($this->getItemCommentStatus()) {
		case SiteCommentStatus::OPEN:
			$status = SiteComment::STATUS_PUBLISHED;
			break;

		case SiteCommentStatus::MODERATED:
			$status = SiteComment::STATUS_PENDING;
			break;
		}

		$this->comment->fullname   = $this->getFullname();
		$this->comment->link       = $this->getLink();
		$this->comment->email      = $this->getEmail();
		$this->comment->bodytext   = $this->getBodytext();
		$this->comment->ip_address = $this->getIPAddress();
		$this->comment->user_agent = $this->getUserAgent();
		$this->comment->createdate = $now;
		$this->comment->status     = $status;
	}




	protected function saveComment()
	{
		if ($this->getParameter('remember_me', false)) {
			$this->saveCookie();
		} else {
			$this->deleteCookie();
		}

		$this->comment->spam = $this->isSpam();
		$this->item->addComment($this->comment);
		$this->item->save();
		$this->comment->postSave($this->app);
	}




	protected function getParameter($name, $required = true)
	{
		$value = SiteApplication::initVar(
			$name, null, SiteApplication::VAR_POST);

		if ($value == '' && $required) {
			throw new SiteCommentJSONException(
				sprintf(
					Site::_('The %s field is required.'),
					$name
				)
			);
		}

		return $value;
	}




	protected function getFullname()
	{
		return $this->getParameter('fullname', true);
	}




	protected function getLink()
	{
		return $this->getParameter('link', false);
	}




	protected function getEmail()
	{
		return $this->getParameter('email', true);
	}




	protected function getBodytext()
	{
		return $this->getParameter('bodytext', true);
	}




	protected function getIPAddress()
	{
		$ip_address = $this->app->getRemoteIP(255);
	}




	protected function getUserAgent()
	{
		$user_agent = null;

		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$user_agent = mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 255);
		}

		return $user_agent;
	}




	protected function isSpam()
	{
		return false;
	}




	protected function getItemCommentStatus()
	{
		return $this->item->getCommentStatus();
	}




	protected function saveCookie()
	{
		if ($this->app->hasModule('SiteCookieModule')) {
			$cookie = $this->app->getModule('SiteCookieModule');
			$value = ['fullname' => $this->getParameter('fullname', true), 'link'     => $this->getParameter('link', false), 'email'    => $this->getParameter('email', true)];

			$cookie->setCookie('comment_credentials', $value);
		}
	}




	protected function deleteCookie()
	{
		if ($this->app->hasModule('SiteCookieModule')) {
			$cookie = $this->app->getModule('SiteCookieModule');
			$cookie->removeCookie('comment_credentials');
		}
	}




	protected function handleException(Throwable $e)
	{
		$this->response = ['status'  => 'error', 'message' => $e->getMessage(), 'type'    => $e::class];
	}



	// build phase


	public function build()
	{
		parent::build();

		if (count($this->response) === 0) {
			// no error occurred, build success response
			$this->buildResponse();
		}

		$this->layout->startCapture('content');
		echo json_encode($this->response);
		$this->layout->endCapture();
	}




	protected function buildResponse()
	{
		$view = $this->getView();
		ob_start();
		$view->display($this->comment);
		$view_content = ob_get_clean();

		$this->response = ['status'         => 'success', 'view'           => $view_content, 'id'             => $this->comment->id, 'comment_status' => $this->comment->status];
	}




	protected function getView()
	{
		return SiteViewFactory::get($this->app, 'comment');
	}


}

?>
