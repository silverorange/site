<?php

require_once 'Swat/SwatButton.php';

/**
 * A button without an XHTML name
 *
 * This is useful for HTTP GET forms where you want to have button ids for
 * style but not button names.
 *
 * @package   Site
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteUnnamedButton extends SwatButton
{
	// {{{ public function display()

	public function display(SwatDisplayContext $context)
	{
		if (!$this->visible) {
			return;
		}

		SwatControl::display($context);

		$form = $this->getFirstAncestor('SwatForm');
		$primary = ($form !== null &&
			$form->getFirstDescendant('SwatButton') === $this);

		$input_tag = new SwatHtmlTag('input');
		$input_tag->type = 'submit';
		$input_tag->id = $this->id;
		$input_tag->value = $this->title;
		$input_tag->class = $this->getCSSClassString();

		if ($this->access_key != '') {
			$input_tag->accesskey = $this->access_key;
		}

		$input_tag->display($context);
	}

	// }}}
}

?>
