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


	/**
	 * Displays this template
	 *
	 * @param SiteLayoutData $data the layoput data to use.
	 */
	public function display(SiteLayoutData $data);




	/**
	 * Gets this template rendered as a string
	 *
	 * @param SiteLayoutData $data the layoput data to use.
	 *
	 * @return string this template rendered as a string.
	 */
	public function asString(SiteLayoutData $data);


}

?>
