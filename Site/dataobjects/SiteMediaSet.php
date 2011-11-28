<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteInstance.php';
require_once 'Site/dataobjects/SiteMediaEncodingWrapper.php';

/**
 * A media set object
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMediaSet extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Short, textual identifer for this set
	 *
	 * The shortname must be unique.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * Obfuscate filename
	 *
	 * @var boolean
	 */
	public $obfuscate_filename;

	/**
	 * Whether or not images added to this media set should be saved to a CDN
	 *
	 * @var boolean
	 */
	public $use_cdn;

	// }}}
	// {{{ public function loadByShortname()

	/**
	 * Loads a set from the database with a shortname
	 *
	 * @param string $shortname the shortname of the set
	 * @param SiteInstance $instance optional instance
	 *
	 * @return boolean true if a set was successfully loaded and false if
	 *                  no set was found at the specified shortname.
	 */
	public function loadByShortname($shortname, SiteInstance $instance = null)
	{
		$this->checkDB();

		$found = false;

		$sql = 'select * from %s where shortname = %s';

		$sql = sprintf($sql,
			$this->table,
			$this->db->quote($shortname, 'text'));

		if ($instance instanceof SiteInstance)
			$sql.= sprintf(' and (instance is null or instance = %s)',
				$instance->id);

		$row = SwatDB::queryRow($this->db, $sql);

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$found = true;
		}

		return $found;
	}

	// }}}
	// {{{ public function hasEncoding()

	/**
	 * Checks existance of an encoding by its shortname
	 *
	 * @param string $shortname the shortname of the encoding
	 *
	 * @return boolean whether the encoding with the given shortname exists
	 */
	public function hasEncoding($shortname)
	{
		$found = false;

		foreach ($this->encodings as $encoding) {
			if ($encoding->shortname === $shortname) {
				$found = true;
				break;
			}
		}

		return $found;
	}

	// }}}
	// {{{ public function getEncodingByShortname()

	/**
	 * Gets an encoding of this set based on its shortname
	 *
	 * @param string $shortname the shortname of the encoding
	 *
	 * @return SiteMediaEncoding the encoding with the given shortname
	 */
	public function getEncodingByShortname($shortname)
	{
		foreach ($this->encodings as $encoding) {
			// don't do an explicit equal as encoding shortnames can be numeric,
			// for example the pixel width of the encoding.
			if ($encoding->shortname == $shortname) {
				return $encoding;
			}
		}

		throw new SiteException(sprintf('Media encoding “%s” does not exist.',
			$shortname));
	}

	// }}}
	// {{{ public function getEncodingShortnameById()

	/**
	 * Gets the shortname of an encoding of this set based on its id
	 *
	 * @param integer $id the id of the encoding
	 *
	 * @return string the shortname of the encoding
	 */
	public function getEncodingShortnameById($id)
	{
		foreach ($this->encodings as $encoding) {
			if ($encoding->id === $id) {
				return $encoding->shortname;
			}
		}

		throw new SiteException(sprintf('Media encoding “%s” does not exist.',
			$id));
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));

		$this->table = 'MediaSet';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function getSerializableSubdataobjects()

	protected function getSerializableSubdataobjects()
	{
		return array(
			'encodings',
		);
	}

	// }}}

	// loader methods
	// {{{ protected function loadEncodings()

	/**
	 * Loads the encodings belonging to this set
	 *
	 * @return SiteMediaEncodingWrapper a set of encoding data objects
	 */
	protected function loadEncodings()
	{
		$sql = 'select * from MediaEncoding
			where media_set = %s
			order by %s';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'),
			$this->getEncodingsOrderBy());

		return SwatDB::query($this->db, $sql, $this->getEncodingWrapper());
	}

	// }}}
	// {{{ protected function getEncodingWrapper()

	protected function getEncodingWrapper()
	{
		return SwatDBClassMap::get('SiteMediaEncodingWrapper');
	}

	// }}}
	// {{{ protected function getEncodingsOrderBy()

	protected function getEncodingsOrderBy()
	{
		return 'id';
	}

	// }}}
}

?>
