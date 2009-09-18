<?php

/**
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteMailingList
{
	// {{{ protected properties

	protected $app;
	protected $shortname;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, $shortname = null)
	{
		$this->app       = $app;
		$this->shortname = $shortname;
	}

	// }}}
	// {{{ abstract public function subscribe()

	abstract public function subscribe($address, $info = array(),
		$array_map = array());

	// }}}
	// {{{ abstract public function unsubscribe()

	abstract public function unsubscribe($address);

	// }}}
	// {{{ abstract public function saveCampaign()

	abstract public function saveCampaign(SiteMailingCampaign $campaign);

	// }}}
}

?>
