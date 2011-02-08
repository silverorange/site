<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A task that should be preformed to a CDN in the near future
 *
 * @package   Site
 * @copyright 2010-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteCdnTask extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $id;

	/**
	 * @var string
	 */
	public $operation;

	/**
	 * @var string
	 */
	public $file_path;

	/**
	 * @var SwatDate
	 */
	public $error_date;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerDateProperty('error_date');

		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
