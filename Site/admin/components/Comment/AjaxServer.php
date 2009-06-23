<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Site/dataobjects/SiteComment.php';
require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Site/layouts/SiteXMLRPCServerLayout.php';
require_once 'Services/Akismet2.php';

/**
 * Performs actions on comments via AJAX
 *
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteCommentAjaxServer extends SiteXMLRPCServer
{
	// {{{ public function spam()

	/**
	 * Marks a comment as spam
	 *
	 * @param integer $comment_id the id of the comment to mark as spam.
	 *
	 * @return boolean true.
	 */
	public function spam($comment_id)
	{
		$comment_class = SwatDBClassMap::get('SiteComment');
		$comment = new $comment_class();
		$comment->setDatabase($this->app->db);
		if ($comment->load($comment_id, $this->app->getInstance())) {
			if (!$comment->spam) {
				// submit spam to akismet
				if ($this->app->config->comment->akismet_key !== null) {
					$uri = $this->app->getBaseHref();
					try {
						$akismet = new Services_Akismet2($uri,
							$this->app->config->comment->akismet_key);

						$akismet_comment = $this->getAkismetComment($comment);

						$akismet->submitSpam($akismet_comment);
					} catch (Exception $e) {
					}
				}

				$comment->spam = true;
				$comment->save();
				$this->flushCache();
			}
		}

		return true;
	}

	// }}}
	// {{{ public function notSpam()

	/**
	 * Marks a comment as not spam
	 *
	 * @param integer $comment_id the id of the comment to mark as not spam.
	 *
	 * @return boolean true.
	 */
	public function notSpam($comment_id)
	{
		$comment_class = SwatDBClassMap::get('SiteComment');
		$comment = new $comment_class();
		$comment->setDatabase($this->app->db);
		if ($comment->load($comment_id, $this->app->getInstance())) {
			if ($comment->spam) {

				// submit false positive to akismet
				if ($this->app->config->comment->akismet_key !== null) {
					$uri = $this->app->getBaseHref();
					try {
						$akismet = new Services_Akismet2($uri,
							$this->app->config->comment->akismet_key);

						$akismet_comment = $this->getAkismetComment($comment);

						$akismet->submitFalsePositive($akismet_comment);
					} catch (Exception $e) {
					}
				}

				$comment->spam = false;
				$comment->save();
				$this->flushCache();
			}
		}

		return true;
	}

	// }}}
	// {{{ public function publish()

	/**
	 * Publishes a comment
	 *
	 * @param integer $comment_id the id of the comment to publish.
	 *
	 * @return boolean true.
	 */
	public function publish($comment_id)
	{
		$class_name = SwatDBClassMap::get('SiteComment');
		$comment = new $class_name();
		$comment->setDatabase($this->app->db);
		if ($comment->load($comment_id, $this->app->getInstance())) {
			if ($comment->status !== SiteComment::STATUS_PUBLISHED) {
				$comment->status = SiteComment::STATUS_PUBLISHED;
				$comment->save();
				$this->flushCache();
			}
		}

		return true;
	}

	// }}}
	// {{{ public function unpublish()

	/**
	 * Unpublishes a comment
	 *
	 * @param integer $comment_id the id of the comment to unpublish.
	 *
	 * @return boolean true.
	 */
	public function unpublish($comment_id)
	{
		$class_name = SwatDBClassMap::get('SiteComment');
		$comment = new $class_name();
		$comment->setDatabase($this->app->db);
		if ($comment->load($comment_id, $this->app->getInstance())) {
			if ($comment->status !== SiteComment::STATUS_UNPUBLISHED) {
				$comment->status = SiteComment::STATUS_UNPUBLISHED;
				$comment->save();
				$this->flushCache();
			}
		}

		return true;
	}

	// }}}
	// {{{ public function delete()

	/**
	 * Deletes a comment
	 *
	 * @param integer $comment_id the id of the comment to delete.
	 *
	 * @return boolean true.
	 */
	public function delete($comment_id)
	{
		$class_name = SwatDBClassMap::get('SiteComment');
		$comment = new $class_name();
		$comment->setDatabase($this->app->db);
		if ($comment->load($comment_id, $this->app->getInstance())) {
			$comment->delete();
			$this->flushCache();
		}

		return true;
	}

	// }}}
	// {{{ abstract protected function getPermalink()

	abstract protected function getPermalink(SiteComment $comment);

	// }}}
	// {{{ protected function getAkismetComment()

	protected function getAkismetComment(SiteComment $comment)
	{
		return new Services_Akismet2_Comment(
			array(
				'comment_author'       => $comment->fullname,
				'comment_author_email' => $comment->email,
				'comment_author_url'   => $comment->link,
				'comment_content'      => $comment->bodytext,
				'permalink'            => $this->getPermalink($comment),
				'user_ip'              => $comment->ip_address,
				'user_agent'           => $comment->user_agent,
			)
		);
	}

	// }}}
	// {{{ protected function flushCache()

	protected function flushCache()
	{

	}

	// }}}
}

?>
