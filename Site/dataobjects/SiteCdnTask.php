<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A task that should be preformed to a CDN in the near future
 *
 * @package   Site
 * @copyright 2010-2012 silverorange
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
	 * A serialized string of http headers to use with the task.
	 *
	 * If set, these will override any headers set by the class.
	 *
	 * @var string
	 */
	public $override_http_headers;

	/**
	 * @var string
	 */
	public $file_path;

	/**
	 * @var SwatDate
	 */
	public $error_date;

	// }}}
	// {{{ public function run()

	/**
	 * Runs a CDN Task Operation
	 *
	 * @param SiteCdn $cdn the CDN this task is executed on.
	 */
	public function run(SiteCdnModule $cdn)
	{
		try {
			$transaction = new SwatDBTransaction($this->db);

			// always delete the object, will be rolled back by the transaction
			// if anything fails
			$this->delete();

			switch ($this->operation) {
			case 'copy':
			case 'update':
				$this->copy($cdn);
				break;
			case 'delete':
			case 'remove':
				$this->remove($cdn);
				break;
			default:
				$this->error();
				break;
			}

			$transaction->commit();
		} catch (SwatDBException $e) {
			$transaction->rollback();
			$e->processAndContinue();
		} catch (SiteCdnException $e) {
			$transaction->rollback();
			$e->processAndContinue();
			$this->error();
		} catch (Exception $e) {
			$transaction->rollback();

			$e = new SiteCdnException($e);
			$e->processAndContinue();
			$this->error();
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
		return (($this->error_date instanceof SwatDate) ?
			Site::_('error.') :
			Site::_('done.'))."\n";
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerDateProperty('error_date');

		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ abstract protected function copy()

	/**
	 * Updates the CDN with the file contained in this task
	 *
	 * @param SiteCdn $cdn the CDN this task is executed on.
	 */
	abstract protected function copy(SiteCdnModule $cdn);

	// }}}
	// {{{ abstract protected function remove()

	/**
	 * Removes the file contained in this task from the CDN
	 *
	 * @param SiteCdn $cdn the CDN this task is executed on.
	 */
	abstract protected function remove(SiteCdnModule $cdn);

	// }}}
	// {{{ protected function error()

	protected function error()
	{
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
		case 'copy':
		case 'update':
			$description_string = Site::_('Updating %1$s ‘%2$s’ ... ');
			break;
		case 'delete':
		case 'remove':
			$description_string = Site::_('Removing ‘%3$s’ ... ');
			break;
		default:
			$description_string = Site::_('Unknown operation ‘%4$s’ ... ');
		}

		return $description_string;
	}

	// }}}
	// {{{ protected function getAccessType()

	protected function getAccessType()
	{
		return 'private';
	}

	// }}}
}

?>
