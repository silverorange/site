<?php

/**
 * @package   Site
 * @copyright 2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteJSONTemplate implements SiteTemplateInterface
{
	// {{{ public function display()

	public function display(SiteLayoutData $data)
	{
		// Note: This mime-type is not official and will prevent JSON content
		// from being viewable in most browsers. It is, however, a defacto
		// standard and YUI expects JSON requests to use this mime-type. Other
		// alternative mime-types are text/javascript, or text/plain.
		header('Content-Type: application/json; charset=utf-8');

		echo $data->content;
	}

	// }}}
}

?>
