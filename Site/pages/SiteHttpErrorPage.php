<?php

require_once 'Site/pages/SitePage.php';
require_once 'Swat/SwatHtmlTag.php';

/**
 * @package   Site
 * @copyright 2006-2007 silverorange
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

		if ($this->http_status_code === null) {
			if (isset($_SERVER['REDIRECT_STATUS'])) {
				$this->http_status_code = intval($_SERVER['REDIRECT_STATUS']);
			} else {
				$this->http_status_code = 500;
			}
		}

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
	// {{{ public function setStatus()

	/**
	 * Sets the HTTP status code for this error page
	 *
	 * @param integer $status the HTTP status code.
	 */
	public function setStatus($status)
	{
		$this->http_status_code = (integer)$status;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

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
		printf(Site::_('HTTP status code: %s').'<br />', $this->http_status_code);
		printf(Site::_('URI: %s').'<br />', $this->uri);
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

		$suggestions['contact'] = Site::_(
			'If you followed a link from our site or elsewhere, please '.
			'contact us and let us know where you came from so we can do our '.
			'best to fix it.');

		$suggestions['typo'] = Site::_(
			'If you typed in the address, please double check the spelling.');

		return $suggestions;
	}

	// }}}
	// {{{ protected function sendHttpStatusHeader()

	protected function sendHttpStatusHeader()
	{
		switch($this->http_status_code) {
		case 400:
			header('HTTP/1.0 400 Bad Request');
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
	// {{{ protected function getTitle()

	protected function getTitle()
	{
		switch($this->http_status_code) {
		case 400:
			return Site::_('Bad Request');
		case 404:
			return Site::_('Page Not Found');
		case 403:
			return Site::_('Forbidden');
		case 500:
		default:
			return Site::_('Internal Server Error');
		}
	}

	// }}}
	// {{{ protected function getSummary()

	protected function getSummary()
	{
		switch($this->http_status_code) {
		case 404:
			return Site::_('Sorry, we couldn’t find the page you were looking for.');
		case 403:
			return Site::_('Sorry, the page you requested is not accessible.');
		case 500:
		default:
			return Site::_('Sorry, there was a problem loading the page you '.
				'requested.');
		}
	}

	// }}}
}

?>
