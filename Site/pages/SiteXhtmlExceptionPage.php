<?php

require_once 'Site/pages/SiteExceptionPage.php';

/**
 * A page to display exceptions
 *
 * @package   Site
 * @copyright 2006-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteXhtmlExceptionPage extends SiteExceptionPage
{
	// build phase
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
		$this->display();
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

	protected function display()
	{
		printf('<p>%s</p>', $this->getSummary());
		$this->displaySuggestions();

		if ($this->exception instanceof SwatException &&
			!($this->exception instanceof SiteNotAuthorizedException)) {
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
}

?>
