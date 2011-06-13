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
	 * @var SwatException
	 */
	protected $exception = null;

	// }}}
	// {{{ public function setException()

	public function setException($e)
	{
		if (!($e instanceof SwatException))
			$e = new SwatException($e);

		$this->exception = $e;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();
		$this->setHttpStatusHeader($this->getHttpStatusCode());
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		$this->layout->data->title = $this->getTitle();
	}

	// }}}
	// {{{ protected function buildContent()

	protected function buildContent()
	{
		$this->layout->startCapture('content');
		$this->display($this->getHttpStatusCode());
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		if (isset($this->layout->navbar)) {
			$this->layout->navbar->createEntry($this->getTitle());
		}
	}

	// }}}
	// {{{ protected function display()

	protected function display($status)
	{
		printf('<p>%s</p>', $this->getSummary($status));
		$this->displaySuggestions();

		if ($this->exception !== null) {
			$this->exception->processAndContinue();
		}
	}

	// }}}
	// {{{ protected function displaySuggestions()

	protected function displaySuggestions()
	{
		$suggestions = $this->getSuggestions();

		if (count($suggestions) == 0)
			return;

		echo '<ul class="spaced">';
		$li_tag = new SwatHtmlTag('li');

		foreach ($suggestions as $suggestion) {
			$li_tag->setContent($suggestion, 'text/xml');
			$li_tag->display();
		}

		echo '</ul>';
	}

	// }}}
	// {{{ protected function getSuggestions()

	protected function getSuggestions()
	{
		return array();
	}

	// }}}
	// {{{ protected function getHttpStatusCode()

	protected function getHttpStatusCode()
	{
		if ($this->exception === null ||
			!($this->exception instanceof SiteException))
				return 500;

		return $this->exception->http_status_code;
	}

	// }}}
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

	protected function getSummary($status)
	{
		switch ($status) {
		case 401:
			return Site::_('Sorry, you must log in to view this page.');
		case 404:
			return Site::_('Sorry, we couldn’t find the page you were looking for.');
		case 403:
			return Site::_('Sorry, the page you requested is not accessible.');
		default:
		case 500:
			return Site::_('Sorry, there was a problem loading the  page you requested.');
		}
	}

	// }}}
	// {{{ protected function setHttpStatusHeader()

	protected function setHttpStatusHeader($status)
	{
		switch ($status) {
		case 401:
			header('HTTP/1.0 401 Unauthorized');
			break;
		case 403:
			header('HTTP/1.0 403 Forbidden');
			break;
		case 404:
			header('HTTP/1.0 404 Not Found');
			break;
		default:
		case 500:
			header('HTTP/1.0 500 Internal Server Error');
			break;
		}
	}

	// }}}
}

?>
