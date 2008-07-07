<?php

require_once 'Swat/SwatUI.php';
require_once 'Swat/SwatDateEntry.php';
require_once 'Swat/exceptions/SwatInvalidPropertyException.php';
require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/pages/SitePage.php';

/**
 * Base class for edit pages
 *
 * @package   Site
 * @copyright 2008 silverorange
 */
abstract class SiteEditPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var SwatUI
	 */
	protected $ui;

	/**
	 * @var string
	 */
	protected $ui_xml;

	// }}}
	// {{{ abstract protected function isNew()

	abstract protected function isNew(SwatForm $form);

	// }}}
	// {{{ protected function getForms()

	protected function getForms()
	{
		$forms = $this->ui->getDescendantWidgets('SwatForm');

		return $forms;
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->ui_xml);

		$this->initInternal();

		$this->ui->init();
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		foreach ($this->getForms() as $form)
			$form->action = $this->source;
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		foreach ($this->getForms() as $form)
			$this->processForm($form);
	}

	// }}}
	// {{{ protected function processForm()

	protected function processForm(SwatForm $form)
	{
		if ($this->authenticate($form)) {
			$form->process();

			if ($form->isProcessed()) {
				$this->validate($form);

				if ($this->isValid($form)) {
					$this->save($form);
					$this->relocate($form);
				} else {
					$this->app->messages->add($this->getInvalidMessage($form));
				}
			}
		}
	}

	// }}}
	// {{{ protected function getInvalidMessage()

	protected function getInvalidMessage(SwatForm $form)
	{
		$message = new SwatMessage('There is a problem with '.
			'the information submitted.', SwatMessage::ERROR);

		$message->secondary_content = 'Please address the '.
			'fields highlighted below and re-submit the form.';

		return $message;
	}

	// }}}
	// {{{ protected function authenticate()

	/**
	 * Authenticate a form on the page
	 */
	protected function authenticate(SwatForm $form)
	{
		$authenticated = true;

		if (!$form->isAuthenticated()) {
			$authenticated = false;
			$message = new SwatMessage('There is a problem with the '.
				'information submitted.', SwatMessage::WARNING);

			$message->secondary_content = 'In order to ensure your security, '.
				'we were unable to process your request. Please try again.';

			$this->app->messages->add($message);
		}

		return $authenticated;
	}

	// }}}
	// {{{ protected function validate()

	protected function validate(SwatForm $form)
	{
	}

	// }}}
	// {{{ abstract protected function save()

	abstract protected function save(SwatForm $form);

	// }}}
	// {{{ abstract protected function relocate()

	abstract protected function relocate(SwatForm $form);

	// }}}
	// {{{ protected function assignUiValuesToObject()

	protected function assignUiValuesToObject(
		SwatDBDataObject $object, array $names)
	{
		foreach ($names as $name)
			$this->assignUiValueToObject($object, $name);
	}

	// }}}
	// {{{ protected function assignUiValueToObject()

	protected function assignUiValueToObject(SwatDBDataObject $object, $name)
	{
		$widget = $this->ui->getWidget($name);
		if ($widget instanceof SwatDateEntry) {
			$value = clone $widget->value;
			$value->setTZ($this->app->default_time_zone);
			$value->toUTC();
		} else {
			$value = $widget->value;
		}

		if (property_exists($object, $name) ||
			$object->hasInternalValue($name)) {

			$object->$name = $value;
		} else {
			throw new SwatInvalidPropertyException(sprintf(
				'Specified “%s” object does not have a property “%s”.',
				get_class($object), $name));
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->buildTitle();
		$this->buildNavBar();

		foreach ($this->getForms() as $form)
			$this->buildForm($form);

		$this->buildInternal();

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		foreach ($this->app->messages->getAll() as $message)
			$this->ui->getWidget('message_display')->add($message);
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm(SwatForm $form)
	{
		if (!$form->isProcessed() && !$this->isNew($form))
			$this->load($form);
	}

	// }}}
	// {{{ abstract protected function load()

	abstract protected function load(SwatForm $form);

	// }}}
	// {{{ protected function assignObjectValuesToUi()

	protected function assignObjectValuesToUi(
		SwatDBDataObject $object, array $names)
	{
		foreach ($names as $name)
			$this->assignObjectValueToUi($object, $name);
	}

	// }}}
	// {{{ protected function assignObjectValueToUi()

	protected function assignObjectValueToUi(SwatDBDataObject $object, $name)
	{
		if (property_exists($object, $name)) {
			$value = $object->$name;
		} elseif ($object->hasInternalValue($name)) {
			$value = $object->getInternalValue($name);
		} else {
			throw new SwatInvalidPropertyException(sprintf(
				'Specified “%s” record does not have a property “%s”.',
				get_class($object), $name));
		}

		$widget = $this->ui->getWidget($name);

		if ($widget instanceof SwatDateEntry) {
			$value = new SwatDate($value);
			$value->convertTZ($this->app->default_time_zone);
		}

		$widget->value = $value;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
