<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteMediaSet.php';
require_once 'Site/dataobjects/SiteMediaType.php';

/**
 * A media encoding object
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaEncoding extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * The unique identifier of this media encoding
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Short textual identifer for this encoding
	 *
	 * The shortname must be unique within this encoding's set.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * Title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Whether or not this is a default encoding for all media
	 *
	 * If true all media that with an original width equal to or larger than
	 * the width of this encoding should be transcoded with this encoding. If
	 * false media is selectively transcoded with this encoding.
	 *
	 * @var boolean
	 */
	public $default_encoding = true;

	// }}}
	// {{{ private properties

	private static $default_type_cache = array();

	// }}}
	// {{{ public function loadByShortname()

	/**
	 * Loads an encoding from the database with a shortname
	 *
	 * @param string $set_shortname the shortname of the set
	 * @param string $encoding_shortname the shortname of the encoding
	 * @param SiteInstance $instance optional instance
	 *
	 * @return boolean true if a encoding was successfully loaded and false if
	 *                  no encoding was found at the specified shortname.
	 */
	public function loadByShortname($set_shortname, $encoding_shortname,
		SiteInstance $instance = null)
	{
		$this->checkDB();

		$found = false;

		$sub_sql = sprintf('select id from MediaSet where shortname = %s',
			$this->db->quote($set_shortname, 'text'));

		if ($instance instanceof SiteInstance) {
			$sub_sql.= sprintf(' and (instance is null or instance = %s)',
				$instance->id);
		}

		$sql = 'select * from %s where shortname = %s and media_set in (%s)';

		$sql = sprintf($sql,
			$this->table,
			$this->db->quote($encoding_shortname, 'text'),
			$sub_sql);

		$row = SwatDB::queryRow($this->db, $sql);

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$found = true;
		}

		return $found;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('media_set',
			SwatDBClassMap::get('SiteMediaSet'));

		$this->registerInternalProperty('default_type',
			SwatDBClassMap::get('SiteMediaType'));

		$this->table = 'MediaEncoding';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function hasSubDataObject()

	protected function hasSubDataObject($key)
	{
		$found = parent::hasSubDataObject($key);

		if ($key === 'default_type' && !$found) {
			$default_type_id = $this->getInternalValue('default_type');

			if ($default_type_id !== null &&
				array_key_exists($default_type_id, self::$default_type_cache)) {
				$this->setSubDataObject('default_type',
					self::$default_type_cache[$default_type_id]);

				$found = true;
			}
		}

		return $found;
	}

	// }}}
	// {{{ protected function setSubDataObject()

	protected function setSubDataObject($name, $value)
	{
		if ($name === 'default_type')
			self::$default_type_cache[$value->id] = $value;

		parent::setSubDataObject($name, $value);
	}

	// }}}
	// {{{ protected function getSerializableSubDataObjects()

	protected function getSerializableSubDataObjects()
	{
		return array(
			'default_type',
		);
	}

	// }}}
}

?>
