<?php

require_once 'Site/pages/SitePage.php';

/**
 * @package   veseys2
 * @copyright 2005 silverorange
 */
class SiteExceptionPage extends SitePage
{
	// {{{ private properties

	private $exception = null;

	// }}}
	// {{{ public function setException()

	public function setException($e)
	{
		$this->exception = $e;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$title  = $this->getTitle();

		if (isset($this->layout->navbar))
			$this->layout->navbar->createEntry($title);

		$status = $this->getHttpStatusCode();
		$this->setHttpStatusHeader($status);

		$this->layout->data->title = $title;

		$this->layout->startCapture('content');
		$this->display($status);
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function display()

	protected function display($status)
	{
		printf('<p>%s</p>', $this->getSummary($status));

		echo '<ul class="spaced">
				<li>If you followed a link from our site or elsewhere, please <a href="about/contact">contact us</a> and let us know where you came from so we can do our best to fix it.</li>
				<li>If you typed in the address, please double check the spelling.</li>
				<li>If you are looking for a product or product information, try browsing the product listing to the left or using the search box on the top right.</li>
			</ul>';

		if ($this->exception !== null)
			$this->exception->process(false);
	}

	// }}}
	// {{{ private function getHttpStatusCode()

	private function getHttpStatusCode()
	{
		if ($this->exception === null ||
			!($this->exception instanceof SiteException))
				return 500;

		return $this->exception->http_status_code;
	}

	// }}}
	// {{{ private function getTitle()

	private function getTitle()
	{
		if ($this->exception === null)
			$title = 'Unknown Error';
		elseif ($this->exception instanceof SiteException && 
			$this->exception->title !== null)
				$title = $this->exception->title;
		else
			$title = 'Error';

		return $title;
	}

	// }}}
	// {{{ private function getSummary()

	private function getSummary($status)
	{
		switch($status) {
		case 404:
			return 'Sorry, we couldn&#8217;t find the page you were looking for.';
		case 403:
			return 'Sorry, the page you requested is not accessible.';
		default:
		case 500:
			return 'Sorry, there was a problem loading the  page you requested.';
		}
	}

	// }}}
	// {{{ private function setHttpStatusHeader()

	private function setHttpStatusHeader($status)
	{
		switch ($status) {
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
