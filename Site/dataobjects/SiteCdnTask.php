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
	// {{{ abstract public function run()

	/**
	 * Copies a file to the CDN
	 *
	 * @param SiteCdn $cdn the CDN this task is executed on.
	 */
	abstract public function run(SiteCdnModule $cdn);

	// }}}
	// {{{ abstract public function getAttemptDescription()

	/**
	 * Gets a string describing the what this task is attempting to achieve
	 */
	abstract public function getAttemptDescription();

	// }}}
	// {{{ abstract public function getResultDescription()

	/**
	 * Gets a string describing the what this task did achieve
	 */
	abstract public function getResultDescription();

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
