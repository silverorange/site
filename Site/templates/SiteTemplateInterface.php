<?php

/**
 * Base interface for templates
 *
 * @package   Site
 * @copyright 2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
interface SiteTemplateInterface
{
	// {{{ public function display()

	/**
	 * Displays this template
	 *
	 * @param SiteLayoutData $data the layoput data to use.
	 */
	public function display(SiteLayoutData $data);

	// }}}
}

?>
