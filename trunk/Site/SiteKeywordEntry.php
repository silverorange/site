<?php

require_once 'Swat/SwatEntry.php';

/**
 * An entry widget where it is possible to specify the input name manually
 *
 * This is useful for HTTP GET forms where the input name is displayed in the
 * request URI.
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 */
class SiteKeywordEntry extends SwatEntry
{
	// {{{ public properties

	/**
	 * The name of this keyword entry widget
	 *
	 * The name is used as the XHTML form element name. It will be displayed
	 * in the URI if the parent form is set to use HTTP GET.
	 *
	 * @var string
	 */
	public $name;

	// }}}
	// {{{ protected function getInputTag()

	protected function getInputTag()
	{
		$tag = parent::getInputTag();
		if ($this->name !== null)
			$tag->name = $this->name;

		return $tag;
	}

	// }}}
}

?>
