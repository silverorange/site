<?php

require_once 'SwatDB/SwatDBDataObject.php';

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

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table    = 'ImageCdnQueue';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function getImageId()

	/**
	 * Gets the id of this task's image
	 *
	 * @return integer the id of this task's image.
	 */
	protected function getImageId()
	{
		$ruins  = explode('/', $this->image_path);
		$debris = explode('.', $ruins[3]);

		return intval($debris[0]);
	}

	// }}}
	// {{{ protected function getDimensionShortname()

	/**
	 * Gets the shortname of this task's image dimension
	 *
	 * @return string the shortname of this task's image dimension.
	 */
	protected function getDimensionShortname()
	{
		$debris = explode('/', $this->image_path);

		return $debris[2];
	}

	// }}}
}

?>
