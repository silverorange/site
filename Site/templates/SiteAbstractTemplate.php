<?php

/**
 * @package   Site
 * @copyright 2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteAbstractTemplate implements SiteTemplateInterface
{
	// {{{ public function asString()

	public function asString(SiteLayoutData $data)
	{
		ob_start();
		$this->display($data);
		return ob_get_clean();
	}

	// }}}
}

?>
