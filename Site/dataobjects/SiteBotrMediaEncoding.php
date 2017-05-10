<?php


/**
 * A BOTR-specific media encoding object
 *
 * @package   Site
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaEncoding extends SiteVideoMediaEncoding
{
	// {{{ public properties

	/**
	 * BOTR key
	 *
	 * @var string
	 */
	public $key;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('media_set',
			SwatDBClassMap::get('SiteBotrMediaSet'));
	}

	// }}}
}

?>
