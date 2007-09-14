<?php

require_once 'Swat/SwatButton.php';

/**
 * A button without an XHTML name
 *
 * This is useful for HTTP GET forms where you want to have button ids for
 * style but not button names.
 *
 * @package   Site
 * @copyright 2006 silverorange
 */
class SiteUnnamedButton extends SwatButton
{
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		$form = $this->getFirstAncestor('SwatForm');
		$primary = ($form !== null &&
			$form->getFirstDescendant('SwatButton') === $this);

		$input_tag = new SwatHtmlTag('input');
		$input_tag->type = 'submit';
		$input_tag->id = $this->id;
		$input_tag->value = $this->title;
		$input_tag->class = $this->getCSSClassString();

		if (strlen($this->access_key) > 0)
			$input_tag->accesskey = $this->access_key;

		$input_tag->display();
	}

	// }}}
}

?>
