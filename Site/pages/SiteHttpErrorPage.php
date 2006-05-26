<?php

require_once 'Site/pages/SitePage.php';

/**
 * @package   Site
 * @copyright 2006 silverorange
 */
class SiteHttpErrorPage extends SitePage
{
	// {{{ protected properties

	protected $http_status_code = null;
	protected $uri = null;

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->http_status_code = isset($_GET['status']) ? $_GET['status'] : 500;

		$exp = explode('/', $this->app->getBaseHref());
		// shift off the 'http://server' part
		array_shift($exp);
		array_shift($exp);
		array_shift($exp);
		$prefix = '/'.implode('/', $exp);
		$len = strlen($prefix);

		if (strncmp($prefix, $_SERVER['REQUEST_URI'], $len) == 0)
			$this->uri = substr($_SERVER['REQUEST_URI'], $len);
		else
			$this->uri = $_SERVER['REQUEST_URI'];
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->addHtmlHeadEntry(
			new SwatHtmlHeadEntry('styles/http-error.css', 
				SwatHtmlHeadEntry::TYPE_STYLE));

		$this->sendHttpStatusHeader();
		$this->layout->data->title  = $this->getTitle();

		$this->layout->startCapture('content');
		$this->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function display()

	protected function display()
	{
		printf('<p>%s</p>', $this->getSummary());

		echo '<ul class="spaced">
				<li>If you followed a link from our site or elsewhere, please <a href="ca/en/about/contact">contact us</a> and let us know where you came from so we can do our best to fix it.</li>
				<li>If you typed in the address, please double check the spelling.</li>
				<li>Get started browsing our site by visiting the <a href="."><strong>Veseys.com home page</strong></a>.</li>
			</ul>';

		printf('HTTP status code: %s<br />', $this->http_status_code);
		printf('URI: %s<br />', $this->uri);
	}

	// }}}
	// {{{ protected function sendHttpStatusHeader()

	protected function sendHttpStatusHeader()
	{
		switch($this->http_status_code) {
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
	// {{{ protected function getTitle()

	protected function getTitle()
	{
		switch($this->http_status_code) {
		case 404:
			return 'Page Not Found';
		case 403:
			return 'Forbidden';
		default:
		case 500:
			return 'Internal Server Error';
		}
	}

	// }}}
	// {{{ protected function getSummary()

	protected function getSummary()
	{
		switch($this->http_status_code) {
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
}
