<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/exceptions/SwatException.php';
require_once 'Swat/exceptions/SwatInvalidClassException.php';
require_once 'Swat/SwatInputControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatState.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatMessage.php';
require_once 'Swat/SwatYUI.php';

/**
 * Control for creating new tags and selecting multiple tags from a array of
 * tags
 *
 * @package   Site
 * @copyright 2008-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteTagEntry extends SwatInputControl implements SwatState
{
	// {{{ public properties

	/*
	 * Whether or not to allow adding new tags
	 *
	 * @var boolean
	 */
	public $allow_adding_tags = true;

	/*
	 * Maximum number of tags
	 *
	 * @var integer
	 */
	public $maximum_tags = null;

	/*
	 * JSON server
	 *
	 * A JSON server that returns an array of name => title pairs based on the
	 * 'query' GET variable. If not specified, all tag values must be specified
	 * using the $tag_array property.
	 */
	public $json_server;

	/*
	 * Whether or not to display the tag shortname in brackets
	 *
	 * @var boolean
	 */
	public $show_shortname = true;

	// }}}
	// {{{ protected properties

	/**
	 * An associative array of tags for the tag flydown in the form of
	 * name => title
	 *
	 * @var array
	 */
	protected $tag_array;

	/**
	 * An array of tag names selected by this tag entry control
	 *
	 * @var array
	 */
	protected $selected_tag_array = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new tag entry control
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->requires_id = true;

		$yui = new SwatYUI(array('autocomplete'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript('packages/swat/javascript/swat-z-index-manager.js',
			Swat::PACKAGE_ID);

		$this->addJavaScript('packages/site/javascript/site-tag-entry.js',
			Site::PACKAGE_ID);

		$this->addStyleSheet('packages/site/styles/site-tag-entry.css',
			Site::PACKAGE_ID);
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this tag entry control
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		SwatWidget::display();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = $this->getCSSClassString();
		$div_tag->id = $this->id;
		$div_tag->open();

		$autocomplete_div = new SwatHtmlTag('div');
		$autocomplete_div->class = 'site-tag-autocomplete-container';
		$autocomplete_div->open();

		$input_tag = new SwatHtmlTag('input');
		$input_tag->name = $this->id.'_value';
		$input_tag->class = 'swat-entry';
		$input_tag->id = $this->id.'_value';

		if (!$this->isSensitive())
			$input_tag->disabled = 'disabled';

		$input_tag->display();

		$container_tag = new SwatHtmlTag('div');
		$container_tag->class = 'site-tag-container';
		$container_tag->id = $this->id.'_container';
		$container_tag->setContent('');
		$container_tag->display();

		$autocomplete_div->close();
		$div_tag->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this tag entry control
	 *
	 * If a validation error occurs, an error message is attached to this
	 * widget.
	 *
	 * @throws SwatException if no database connection is set on this tag
	 *                        entry control.
	 */
	public function process()
	{
		parent::process();

		$this->selected_tag_array = array();

		$data = &$this->getForm()->getFormData();
		$new_key = $this->id.'_new';
		if (isset($data[$new_key]) && is_array($data[$new_key])) {
			foreach ($data[$new_key] as $index => $new_tag) {
				$this->insertTag($new_tag, $index);
			}
		}

		if (isset($data[$this->id]) && is_array($data[$this->id])) {
			$tag_strings = $data[$this->id];

			foreach ($tag_strings as $string) {
				if (isset($this->tag_array[$string])) {
					$this->selected_tag_array[$string] =
						$this->tag_array[$string];
				} else {
					$this->selected_tag_array[$string] = $string;
				}
			}
		}

		if ($this->required && count($this->selected_tag_array) == 0) {
			$message = Swat::_('The %s field is required.');
			$this->addMessage(new SwatMessage($message, SwatMessage::ERROR));
		}
	}

	// }}}
	// {{{ public function getState()

	/**
	 * Gets the current state of this tag entry control
	 *
	 * @return boolean the current state of this tag entry control.
	 *
	 * @see SwatState::getState()
	 */
	public function getState()
	{
		return $this->getSelectedTagArray();
	}

	// }}}
	// {{{ public function setState()

	/**
	 * Sets the current state of this tag entry control
	 *
	 * @param array $state the new state of this tag entry control.
	 *
	 * @see SwatState::setState()
	 *
	 * @throws SwatException if the given state is not an array
	 */
	public function setState($state)
	{
		if (is_array($state))
			$this->selected_tag_array = $state;
		else
			throw new SwatException('State must be an array');
	}

	// }}}
	// {{{ public function getFocusableHtmlId()

	/**
	 * Gets the id attribute of the XHTML element displayed by this widget
	 * that should receive focus
	 *
	 * @return string the id attribute of the XHTML element displayed by this
	 *                 widget that should receive focus or null if there is
	 *                 no such element.
	 *
	 * @see SwatWidget::getFocusableHtmlId()
	 */
	public function getFocusableHtmlId()
	{
		return ($this->visible) ? $this->id.'_value' : null;
	}

	// }}}
	// {{{ public function setTagArray()

	/**
	 * Sets the array of tags that may be selected by this tag entry control
	 *
	 * @param array $tag_array the array of tags that may be selected by
	 *                                  this tag entry control.
	 */
	public function setTagArray(array $tag_array)
	{
		$this->tag_array = $tag_array;
	}

	// }}}
	// {{{ public function setSelectedTagArray()

	/**
	 * Sets the array of tags that are pre-selected for this photo
	 *
	 * @param array $tag_array the array of tags that appear
	 *                       pre-selected for this entry widget.
 	 */
	public function setSelectedTagArray(array $tag_array)
	{
		$this->selected_tag_array = $tag_array;
	}

	// }}}
	// {{{ public function getSelectedTagArray()

	/**
	 * Gets the array of tags selected by this tag entry control
	 *
	 * Call this method after processing this control to get the tags selected
	 * by the user.
	 *
	 * @return array the array of tags selected by this tag entry
	 *                         control.
	 */
	public function getSelectedTagArray()
	{
		return $this->selected_tag_array;
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this tag entry
	 *
	 * @return array the array of CSS classes that are applied to this
	 *                tag entry.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('site-tag-entry');
		$classes = array_merge($classes, parent::getCSSClassNames());
		return $classes;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript for this tag entry control
	 *
	 * @return string the inline JavaScript for this tag entry control.
	 */
	protected function getInlineJavaScript()
	{
		static $shown = false;

		if (!$shown) {
			$javascript = $this->getInlineJavaScriptTranslations();
			$shown = true;
		} else {
			$javascript = '';
		}

		if ($this->json_server === null) {
			$tag_array = array();
			foreach ($this->tag_array as $tag => $title) {
				$tag_array[] = sprintf("\n[%s, %s]",
					SwatString::quoteJavaScriptString(
						SwatString::minimizeEntities($title)),
					SwatString::quoteJavaScriptString(
						SwatString::minimizeEntities($tag)));
			}

			$data_store = sprintf('new YAHOO.widget.DS_JSArray([%s])',
				implode(',', $tag_array));
		} else {
			$data_store = sprintf('new YAHOO.widget.DS_XHR(%s,
				 ["ResultSet.Result", "Title", "Shortname"])',
				SwatString::quoteJavaScriptString($this->json_server));
		}

		$selected_tag_array = array();
		foreach ($this->selected_tag_array as $tag => $title) {
			$selected_tag_array[] = sprintf("\n[%s, %s]",
				SwatString::quoteJavaScriptString(
					SwatString::minimizeEntities($title)),
				SwatString::quoteJavaScriptString(
					SwatString::minimizeEntities($tag)));
		}

		$javascript.= sprintf("var %1\$s_obj = new %2\$s(".
			"'%1\$s', %3\$s, [%4\$s], %5\$s);",
			$this->id,
			$this->getJavaScriptClassName(),
			$data_store,
			implode(',', $selected_tag_array),
			$this->allow_adding_tags ? 'true' : 'false');

		if ($this->maximum_tags !== null) {
			$javascript.= sprintf("\n%s_obj.maximum_tags = %d;",
				$this->id, $this->maximum_tags);
		}

		if (!$this->show_shortname) {
			$javascript.= sprintf("\n%s_obj.show_shortname = false;",
				$this->id);
		}

		return $javascript;
	}

	// }}}
	// {{{ protected function getInlineJavaScriptTranslations()

	/**
	 * Gets translatable string resources for the JavaScript object for
	 * this widget
	 *
	 * @return string translatable JavaScript string resources for this widget.
	 */
	protected function getInlineJavaScriptTranslations()
	{
		$remove_text = SwatString::quoteJavaScriptString(Site::_('remove'));
		$new_text    = SwatString::quoteJavaScriptString(Site::_('(new)'));
		$add_text    = SwatString::quoteJavaScriptString(Site::_('Add Tag'));

		return
			"SiteTagEntry.remove_text = {$remove_text};\n".
			"SiteTagEntry.new_text    = {$new_text};\n".
			"SiteTagEntry.add_text    = {$add_text};\n";
	}

	// }}}
	// {{{ protected function getJavaScriptClassName()

	protected function getJavaScriptClassName()
	{
		return 'SiteTagEntry';
	}

	// }}}
	// {{{ abstract protected function insertTag()

	/**
	 * Creates a new tag
	 */
	abstract protected function insertTag($title, $index);

	// }}}
}

?>
