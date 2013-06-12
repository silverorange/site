<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * API credentials
 *
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteApiCredential extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * The unique identifier of this credential
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The title of the owner of the credentials
	 *
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $api_key;

	/**
	 * @var string
	 */
	public $api_shared_secret;

	/**
	 * The date that these credentials were created
	 *
	 * @var SwatDate
	 */
	public $createdate;

	// }}}
	// {{{ public function loadByApiKey()

	public function loadByApiKey($key)
	{
		$this->checkDB();

		$row = null;

		if ($this->table !== null) {
			$sql = sprintf(
				'select * from %s where api_key = %s',
				$this->table,
				$this->db->quote($key, 'text'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row === null)
			return false;

		$this->initFromRow($row);
		$this->generatePropertyHashes();

		return true;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'ApiCredential';
		$this->id_field = 'integer:id';
		$this->registerDateProperty('createdate');
	}

	// }}}

	// saver methods
	// {{{ protected function saveInternal()

	protected function saveInternal()
	{
		if ($this->id === null) {
			$this->createdate = new SwatDate();
			$this->createdate->toUTC();
		}

		parent::saveInternal();
	}

	// }}}
}

?>
