<?php

require_once 'Site/pages/SitePageDecorator.php';

/**
 * A page decorator to add a message display to the top of a page if their are
 * app messages.
 *
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMessageDisplayPageDecorator extends SitePageDecorator
{
	// {{{ protected properties

	protected $message_display;

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->message_display = new SwatMessageDisplay();
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		foreach ($this->app->messages->getAll() as $message)
			$this->message_display->add($message);

		if ($this->message_display->getMessageCount() > 0) {
			$this->layout->startCapture('content', true);
			$this->message_display->display();
			$this->layout->endCapture();
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		if ($this->message_display->getMessageCount() > 0) {
			$this->layout->addHtmlHeadEntrySet(
				$this->message_display->getHtmlHeadEntrySet());
		}
	}

	// }}}
}

?>
