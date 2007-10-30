<?php

require_once 'Site/pages/SiteSearchResultsPage.php';

/**
 * Page for displaying a search form above search results.
 *
 * @package   Site
 * @copyright 2007 silverorange
 */
class SiteSearchPage extends SiteSearchResultsPage
{
	// {{{ protected properties

	/**
	 * The user-interface of the search form
	 *
	 * @var StoreUI
	 */
	protected $form_ui;

	/**
	 * The SwatML file to load the search user-interface from
	 *
	 * @var string
	 */
	protected $form_ui_xml = 'Site/pages/search-form.xml';

	// }}}

	// init phase
	// {{{ public function init

	public function init()
	{
		parent::init();

		$this->form_ui = new SwatUI();
		$this->form_ui->loadFromXML($this->form_ui_xml);

		$form = $this->form_ui->getWidget('search_form');
		$form->action = $this->source;

		$this->form_ui->init();
	}

	// }}}

	// process phase
	// {{{ public function process

	public function process()
	{
		parent::process();

		$this->form_ui->process();

		/* 
		 * Nothing else to do... 
		 * the parent class result page is driven by the GET variables this
		 * form provided.
		 */
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->layout->startCapture('content');
		$this->form_ui->display();
		$this->layout->endCapture();

		parent::build();
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->form_ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
