<?php

require_once 'Swat/SwatOptionControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatYUI.php';
require_once 'Swat/exceptions/SwatObjectNotFoundException.php';

/**
 * Slider widget to select between different comment statuses
 *
 * This is a SwatOptionControl with each option being the comment status value
 * and the title of the value. While the underlying code is quite flexible, the
 * display uses a background image that requires the number of options to be
 * four.
 *
 * Context-help can be added for options using the
 * {@link SiteCommentStatusSlider::addContextNote()} method.
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteCommentStatusSlider extends SwatOptionControl
{
	// {{{ public properties

	/**
	 * Slider value
	 *
	 * The value of the selected option. Defaults to the first option if set
	 * to null.
	 *
	 * @var mixed
	 */
	public $value = null;

	/**
	 * Context notes of this slider control indexed by option object
	 *
	 * @var array
	 */
	public $context_notes_by_option = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new comment status slider
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);
		$this->requires_id = true;

		$yui = new SwatYUI(array('slider'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript(
			'packages/site/admin/javascript/site-comment-status-slider.js',
			Site::PACKAGE_ID);

		$this->addStyleSheet(
			'packages/site/admin/styles/site-comment-status-slider.css',
			Site::PACKAGE_ID);
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this comment status slider
	 */
	public function process()
	{
		parent::process();

		$form = $this->getForm();
		$data = &$form->getFormData();

		$key = $this->id.'_value';
		if (isset($data[$key])) {
			if ($this->serialize_values) {
				$salt = $form->getSalt();
				$this->value = SwatString::signedUnserialize(
					$data[$key], $salt);
			} else {
				$this->value = (string)$data[$key];
			}
		}
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this comment status slider
	 */
	public function display()
	{
		parent::display();

		$container_div = new SwatHtmlTag('div');
		$container_div->id = $this->id;
		$container_div->class = 'site-comment-status-slider';
		$container_div->open();

		$thumb_div = new SwatHtmlTag('div');
		$thumb_div->id = $this->id.'_thumb';
		$thumb_div->class = 'site-comment-status-slider-thumb';
		$thumb_div->open();

		$img_tag = new SwatHtmlTag('img');
		$img_tag->class = 'site-comment-status-slider-image';
		$img_tag->src =
			'packages/site/admin/images/site-comment-status-slider-thumb.gif';

		$img_tag->alt = '';
		$img_tag->display();

		$thumb_div->close();

		$input_tag = new SwatHtmlTag('input');
		$input_tag->id = $this->id.'_value';
		$input_tag->name = $this->id.'_value';
		$input_tag->type = 'hidden';
		if ($this->serialize_values) {
			$salt = $this->getForm()->getSalt();
			$input_tag->value = SwatString::signedSerialize($this->value,
				$salt);
		} else {
			$input_tag->value = (string)$this->value;
		}
		$input_tag->display();

		$container_div->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ public function addContextNote()

	/**
	 * Adds a context note to an option in this slider
	 *
	 * A context note for the selected option will be displayed underneath the
	 * slider control.
	 *
	 * @param SwatOption $option the option to which to add the note.
	 * @param string $note the note to add.
	 *
	 * @throws SwatObjectNotFoundException if the specified option is not an
	 *                                     option of this slider.
	 */
	public function addContextNote(SwatOption $option, $note)
	{
		if (!in_array($option, $this->options)) {
			throw new SwatObjectNotFoundException(
				'Specified option does not exist in this slider.');
		}

		$this->context_notes_by_option[spl_object_hash($option)] = $note;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript required by this control
	 *
	 * @return string the inline JavaScript required by this control.
	 */
	protected function getInlineJavaScript()
	{
		$salt = $this->getForm()->getSalt();
		$options = array();
		foreach ($this->options as $option) {
			if ($this->serialize_values) {
				$value = SwatString::signedSerialize($option->value, $salt);
			} else {
				$value = (string)$option->value;
			}

			$key = spl_object_hash($option);
			if (array_key_exists($key, $this->context_notes_by_option)) {
				$note = $this->context_notes_by_option[$key];
			} else {
				$note = '';
			}

			$options[] = sprintf('[%s, %s, %s]',
				SwatString::quoteJavaScriptString($value),
				SwatString::quoteJavaScriptString($option->title),
				SwatString::quoteJavaScriptString($note));
		}
		$options = implode(', ', $options);

		return sprintf(
			"var %s_obj = new SiteCommentStatusSlider('%s', [%s]);",
			$this->id,
			$this->id,
			$options);
	}

	// }}}
}

?>
