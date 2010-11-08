<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteImage.php';
require_once 'Site/dataobjects/SiteImageDimension.php';

/**
 * A task that should be preformed to a CDN in the near future
 *
 * @package   Site
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteImageCdnTask extends SwatDBDataObject
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
	public $image_path;

	/**
	 * @var SwatDate
	 */
	public $error_date;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('image',
			SwatDBClassMap::get('SiteImage'));

		$this->registerInternalProperty('dimension',
			SwatDBClassMap::get('SiteImageDimension'));

		$this->registerDateProperty('error_date');

		$this->table    = 'ImageCdnQueue';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
