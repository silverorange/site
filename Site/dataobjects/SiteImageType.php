<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * An image type data object
 *
 * @package   Site
 * @copyright 2008 silverorange
 */
class SiteImageType extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Extension
	 *
	 * @var string
	 */
	public $extension;

	/**
	 * Mime type
	 *
	 * @var string
	 */
	public $mime_type;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'ImageType';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
