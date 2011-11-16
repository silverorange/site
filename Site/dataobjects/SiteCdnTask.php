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
	// {{{ constants

	/**
	 * Common CDN operations.
	 */
	const COPY   = 'copy';
	const DELETE = 'delete';

	// }}}
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
	// {{{ protected properties

	/**
	 * Whether or not this task was successfully completed
	 *
	 * @var boolean
	 */
	protected $success = false;

	// }}}
	// {{{ public function run()

	/**
	 * Runs a CDN Task Operation
	 *
	 * @param SiteCdn $cdn the CDN this task is executed on.
	 */
	public function run(SiteCdnModule $cdn)
	{
		switch ($this->operation) {
		case self::COPY:
			$this->copyItem($cdn);
			break;
		case self::DELETE:
			$this->deleteItem($cdn);
			break;
		default:
			$this->error();
			break;
		}
	}

	// }}}
	// {{{ abstract public function getAttemptDescription()

	/**
	 * Gets a string describing the what this task is attempting to achieve
	 */
	abstract public function getAttemptDescription();

	// }}}
	// {{{ public function getResultDescription()

	/**
	 * Gets a string describing the what this task did achieve
	 */
	public function getResultDescription()
	{
		return (($this->success) ?
			Site::_('done.') :
			Site::_('error.'))."\n";
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerDateProperty('error_date');

		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ abstract protected function copyItem()

	/**
	 * Attempts to copy an item to the CDN
	 *
	 * @param SiteCdn $cdn the CDN this task is executed on.
	 */
	abstract protected function copyItem(SiteCdnModule $cdn);

	// }}}
	// {{{ abstract protected function deleteItem()

	/**
	 * Attempts to delete an item from the CDN
	 *
	 * @param SiteCdn $cdn the CDN this task is executed on.
	 */
	abstract protected function deleteItem(SiteCdnModule $cdn);

	// }}}
	// {{{ protected function error()

	protected function error()
	{
		$this->success = false;

		$this->error_date = new SwatDate();
		$this->error_date->toUTC();
		$this->save();
	}

	// }}}
	// {{{ protected function getAttemptDescriptionString()

	/**
	 * Gets a string to pass into sprintf() in getAttemptDescription(), which
	 * uses numbered replacement markers as follows:
	 * %1$s = Item type
	 * %2$s = item id
	 * %3$s = filepath
	 * %4$s = operation
	 */
	protected function getAttemptDescriptionString()
	{
		switch ($this->operation) {
		case self::COPY:
			$description_string = Site::_('Copying %1$s ‘%2$s’ ... ');
			break;
		case self::DELETE:
			$description_string = Site::_('Deleting ‘%3$s’ ... ');
			break;
		default:
			$description_string = Site::_('Unknown operation ‘%4$s’ ... ');
		}

		return $description_string;
	}

	// }}}
	// {{{ protected function getHttpHeaders()

	protected function getHttpHeaders()
	{
		/* Set a "never-expire" policy with a far future max age (10 years) as
		 * suggested http://developer.yahoo.com/performance/rules.html#expires.
		 * As well, set Cache-Control to public, as this allows some browsers to
		 * cache the images to disk while on https, which is a good win. This
		 * depends on setting new object ids when updating the object, if this
		 * isn't true of a subclass this will have to be overwritten.
		 */
		return array(
			'cache-control' => 'public, max-age=315360000',
		);
	}

	// }}}
}

?>
