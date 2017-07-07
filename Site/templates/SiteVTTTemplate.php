<?php

/**
 * @package   Site
 * @copyright 2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteVTTTemplate implements SiteTemplate
{
	// {{{ public function display()

	public function display(SiteLayoutData $data)
	{
		header('Content-Type: application/vtt; charset=utf-8');

		echo $data->content;
	}

	// }}}
}

?>
