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
			new SwatStyleSheetHtmlHeadEntry('styles/http-error.css',
			Site::PACKAGE_ID));

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
		$this->displaySuggestions();
		printf('HTTP status code: %s<br />', $this->http_status_code);
		printf('URI: %s<br />', $this->uri);
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
		$suggestions = array();

		$suggestions['contact'] =
			'If you followed a link from our site or elsewhere, please '.
			'contact us and let us know where you came from so we can do our '.
			'best to fix it.';

		$suggestions['typo'] =
			'If you typed in the address, please double check the spelling.';

		return $suggestions;
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

?>
