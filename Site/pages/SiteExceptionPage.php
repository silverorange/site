<?php

require_once 'Site/pages/SitePage.php';

/**
 * A page to display exceptions
 *
 * @package   Site
 * @copyright 2006-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteExceptionPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var SiteException
	 */
	protected $exception;

	// }}}
	// {{{ public function setException()

	public function setException($exception)
	{
		if ($exception instanceof SwatException) {
			$this->exception = $exception;
		} else {
			$this->exception = new SiteException($exception);
		}
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->initHttpStatusHeader();
	}

	// }}}
	// {{{ protected function initHttpStatusHeader()

	protected function initHttpStatusHeader()
	{
		switch ($this->getHttpStatusHeader()) {
		case 401:
			header('HTTP/1.0 401 Unauthorized');
			break;
		case 403:
			header('HTTP/1.0 403 Forbidden');
			break;
		case 404:
			header('HTTP/1.0 404 Not Found');
			break;
		case 500:
		default:
			header('HTTP/1.0 500 Internal Server Error');
			break;
		}
	}

	// }}}
	// {{{ protected function getHttpStatusHeader()

	protected function getHttpStatusHeader()
	{
		return ($this->exception instanceof SiteException) ?
			$this->exception->http_status_code : 500;
	}

	// }}}

	// build phase
	// {{{ protected function getTitle()

	protected function getTitle()
	{
		if ($this->exception === null) {
			$title = Site::_('Unknown Error');
		} elseif ($this->exception instanceof SiteException &&
			$this->exception->title !== null) {
				$title = $this->exception->title;
		} else {
			$title = Site::_('Error');
		}

		return $title;
	}

	// }}}
	// {{{ protected function getSummary()

	protected function getSummary()
	{
		switch ($this->getHttpStatusHeader()) {
		case 401:
			return Site::_('Sorry, you must log in to view this page.');
		case 404:
			return Site::_('Sorry, we couldn’t find the page you were looking for.');
		case 403:
			return Site::_('Sorry, the page you requested is not accessible.');
		case 500:
		default:
			return Site::_('Sorry, there was a problem loading the  page you requested.');
		}
	}

	// }}}
	// {{{ protected function getSuggestions()

	protected function getSuggestions()
	{
		return array();
	}

	// }}}
}

?>
