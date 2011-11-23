<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteMediaSet.php';
require_once 'Site/dataobjects/SiteMediaEncodingBindingWrapper.php';

/**
 * A media object
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      Delete local and cdn files on delete like SiteImage and
 *            SiteAttachment.
 */
class SiteMedia extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * The base uri for CDN hosted media
	 *
	 * @var string
	 */
	public static $cdn_base;

	/**
	 * The unique identifier of this media
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Filename
	 *
	 * @var string
	 */
	public $filename;

	/**
	 * Original Filename
	 *
	 * @var string
	 */
	public $original_filename;

	/**
	 * Description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Whether or not the media is Downloadable
	 *
	 * @var boolean
	 */
	public $downloadable = false;

	/**
	 * Duration (in seconds)
	 *
	 * @var integer
	 */
	public $duration;

	/**
	 * The date that this media was created
	 *
	 * @var Date
	 */
	public $createdate;

	// }}}
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $file_base;

	/**
	 * @var string
	 */
	protected $media_set_shortname;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Media';
		$this->id_field = 'integer:id';

		$this->registerDateProperty('createdate');

		$this->registerInternalProperty('media_set',
			SwatDBClassMap::get('SiteMediaSet'));
	}

	// }}}
	// {{{ public function getFormattedDuration()

	/**
	 * Returns the duration of the media in a human readable format.
	 *
	 * DateIntervals were dismissed because creating 10-20 SwatDate's per page
	 * seemed slow and excessive. This is a slightly simplified version of
	 * SwatString::toHumanReadableTimePeriod() to allow for custom formatting.
	 * It always returns the two largest time parts.
	 *
	 * @returns string
	 */
	public function getFormattedDuration()
	{
		$format  = null;
		$hours   = null;
		$minutes = null;
		// don't care about micro-seconds.
		$seconds = floor($this->duration);

		$minute = 60;
		$hour = $minute * 60;

		if ($seconds > $hour) {
			$hours = floor($seconds / $hour);
			$seconds -= $hour * $hours;
		}

		if ($seconds > $minute) {
			$minutes = floor($seconds / $minute);
			$seconds -= $minute * $minutes;
		}

		// use sprintf for padding, because I read somewhere on the internet it
		// was faster than str_pad.
		if ($hours !== null) {
			// Drop the seconds from the format since it seems overkill when we
			// have over an hour of content.
			// 1h, 30m
			$format = Site::_('%1$sh, %2$02sm');
		} elseif ($minutes !== null) {
			// 30:30
			$format.= Site::_('%2$s:%3$02s');
		} else {
			// 30s
			$format = Site::_('%3$ss');
		}

		return sprintf($format,
			$hours,
			$minutes,
			$seconds);
	}

	// }}}
	// {{{ public function isDownloadable()

	public function isDownloadable()
	{
		return $this->downloadable;
	}

	// }}}
	// {{{ public function encodingExists()

	public function encodingExists($encoding_shortname)
	{
		$binding = $this->getEncodingBinding($encoding_shortname);

		return ($binding instanceof SiteMediaEncodingBinding);
	}

	// }}}
	// {{{ public function getDownloadUri()

	public function getDownloadUri($encoding_shortname = null, $prefix = null)
	{
		if ($prefix != null)
			$prefix.= '/';

		$id = $this->id;
		if ($encoding_shortname != null)
			$id.= '-'.$encoding_shortname;

		return sprintf('%smedia%s', $prefix, $id);
	}

	// }}}
	// {{{ public function getMimeType()

	public function getMimeType($encoding_shortname)
	{
		$binding = $this->getEncodingBinding($encoding_shortname);

		if ($binding === null) {
			throw new SiteException(sprintf(
				'Encoding “%s” does not exist for media “%s”.',
					$encoding_shortname, $this->id));
		}

		return $binding->media_type->mime_type;
	}

	// }}}
	// {{{ public function getHumanFileType()

	public function getHumanFileType($encoding_shortname)
	{
		$binding = $this->getEncodingBinding($encoding_shortname);

		if ($binding === null) {
			throw new SiteException(sprintf(
				'Encoding “%s” does not exist for media “%s”.',
					$encoding_shortname, $this->id));
		}

		return $binding->getHumanFileType();
	}

	// }}}
	// {{{ public function getFileSize()

	public function getFileSize($encoding_shortname)
	{
		if ($encoding_shortname === null) {
			$binding = $this->getLargestEncodingBinding();
		} else {
			$binding = $this->getEncodingBinding($encoding_shortname);
		}

		if ($binding === null) {
			throw new SwatException(sprintf(
				'Encoding “%s” does not exist for media “%s”.',
					$encoding_shortname, $this->id));
		}

		return $binding->filesize;
	}

	// }}}
	// {{{ public function getFormattedFileSize()

	public function getFormattedFileSize($encoding_shortname = null)
	{
		if ($encoding_shortname === null) {
			$binding = $this->getLargestEncodingBinding();
		} else {
			$binding = $this->getEncodingBinding($encoding_shortname);
		}

		if ($binding === null) {
			throw new SiteException(sprintf(
				'Encoding “%s” does not exist for media “%s”.',
					$encoding_shortname, $this->id));
		}

		return $binding->getFormattedFileSize();
	}

	// }}}
	// {{{ public function getLargestEncodingBinding()

	public function getLargestEncodingBinding()
	{
		$largest = null;

		foreach ($this->encoding_bindings as $binding) {
			if ($largest === null) {
				$largest = $binding;
			}

			if ($binding->filesize > $largest->filesize) {
				$largest = $binding;
			}
		}

		return $largest;
	}

	// }}}
	// {{{ public function getEncodingBinding()

	public function getEncodingBinding($encoding_shortname)
	{
		$encoding = $this->media_set->getEncodingByShortname(
			$encoding_shortname);

		foreach ($this->encoding_bindings as $binding) {
			$id = ($binding->media_encoding instanceof SiteMediaEncoding) ?
				$binding->media_encoding->id : $binding->media_encoding;

			if ($encoding->id === $id) {
				return $binding;
			}
		}

		return null;
	}

	// }}}
	// {{{ public function setOnCdn()

	/**
	 * Sets the on_cdn column on the media encoding binding
	 *
	 * @param boolean $on_cdn the new value for on_cdn.
	 * @param string $encoding_shortname the shortname of the encoding to
	 *                                    update.
	 */
	public function setOnCdn($on_cdn, $encoding_shortname)
	{
		$encoding = $this->media_set->getEncodingByShortname(
			$encoding_shortname);

		$sql = sprintf('update MediaEncodingBinding set on_cdn = %s where
			media = %s and media_encoding = %s',
			$this->db->quote($on_cdn, 'boolean'),
			$this->db->quote($this->id, 'integer'),
			$this->db->quote($encoding->id, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
	// {{{ public function encodingOnCdn()

	/**
	 * Checks whether a media encoding binding exists on the cdn
	 *
	 * @param string $encoding_shortname the shortname of the encoding to
	 *                                    check.
	 *
	 * @returns boolean
	 */
	public function encodingOnCdn($encoding_shortname)
	{
		$on_cdn = false;

		$encoding = $this->getEncodingBinding($encoding_shortname);
		if ($encoding instanceof SiteMediaEncodingBinding) {
			$on_cdn = $encoding->on_cdn;
		}

		return $on_cdn;
	}

	// }}}
	// {{{ public function load()

	public function load($id)
	{
		$loaded = parent::load($id);

		if ($loaded &&
			$this->media_set_shortname !== null &&
			$this->media_set->shortname !== $this->media_set_shortname) {
			throw new SiteException('Trying to load media with the wrong '.
				'media set. This may happen if the wrong wrapper class is '.
				'used.');
		}

		return $loaded;
	}

	// }}}
	// {{{ protected function loadEncodingBindings()

	protected function loadEncodingBindings()
	{
		$sql = sprintf('select * from MediaEncodingBinding
			where MediaEncodingBinding.media = %s
			order by %s',
			$this->db->quote($this->id, 'integer'),
			$this->getEncodingsOrderBy());

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('SiteMediaEncodingBindingWrapper'));
	}

	// }}}
	// {{{ protected function getEncodingsOrderBy()

	protected function getEncodingsOrderBy()
	{
		return 'id';
	}

	// }}}
	// {{{ protected function getSerializableSubDataObjects()

	protected function getSerializableSubDataObjects()
	{
		return array(
			'media_set',
			'encoding_bindings',
		);
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		return array_merge(parent::getSerializablePrivateProperties(), array(
			'media_set_shortname',
		));
	}

	// }}}

	// Processing methods
	// {{{ protected function queueCdnTask()

	/**
	 * Queues a CDN task to be preformed later
	 *
	 * @param string $operation the operation to preform
	 */
	protected function queueCdnTask($operation, SiteMediaEncoding $encoding)
	{
		$task = new SiteMediaCdnTask();
		$task->setDatabase($this->db);
		$task->operation = $operation;

		if ($operation == 'copy') {
			$task->media    = $this->id;
			$task->encoding = $encoding->id;
		} else {
			$task->file_path = $this->getUriSuffix();
		}

		$task->save();
	}

	// }}}

	// File and URI methods
	// {{{ public function getUri()

	public function getUri($shortname, $prefix = '')
	{
		$uri = $this->getUriSuffix($shortname);

		// Don't apply the prefix if the media exists on a CDN since the media
		// will always be in the same location. We don't need to apply ../ for
		// media displayed in the admin.
		$binding = $this->getEncodingBinding($shortname);
		if ($binding->on_cdn && self::$cdn_base != '') {
			$uri = self::$cdn_base.$uri;
		} else if ($prefix != '' && !strpos($uri, '://')) {
			$uri = $prefix.$uri;
		}

		return $uri;
	}

	// }}}
	// {{{ public function getUriSuffix()

	public function getUriSuffix($encoding_shortname)
	{
		$suffix = sprintf('%s/%s/%s',
			$this->media_set->shortname,
			$encoding_shortname,
			$this->getFilename($encoding_shortname));

		if ($this->getUriBase() != '') {
			$suffix = $this->getUriBase().'/'.$suffix;
		}

		return $suffix;
	}

	// }}}
	// {{{ public function setFileBase()

	public function setFileBase($file_base)
	{
		$this->file_base = $file_base;
	}

	// }}}
	// {{{ public function getFileDirectory()

	public function getFileDirectory($encoding_shortname)
	{
		$items = array(
			$this->getFileBase(),
			$this->media_set->shortname,
			$encoding_shortname,
		);

		return implode(DIRECTORY_SEPARATOR, $items);
	}

	// }}}
	// {{{ public function getFilePath()

	public function getFilePath($encoding_shortname)
	{
		$items = array($this->getFileDirectory($encoding_shortname),
			$this->getFilename($encoding_shortname));

		return implode(DIRECTORY_SEPARATOR, $items);
	}

	// }}}
	// {{{ public function getFilename()

	public function getFilename($encoding_shortname)
	{
		$binding = $this->getEncodingBinding($encoding_shortname);

		return sprintf('%s.%s', $this->id, $binding->media_type->extension);
	}

	// }}}
	// {{{ public function getContentDispositionFilename()

	public function getContentDispositionFilename($encoding_shortname)
	{
		$binding = $this->getEncodingBinding($encoding_shortname);

		$filename = sprintf('%s.%s',
			$encoding_shortname,
			$binding->media_type->extension);

		// Convert to an ASCII string. Approximate non ACSII characters.
		$filename = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);

		// Format the filename according to the qtext syntax in RFC 822
		$filename = str_replace(array("\\", "\r", "\""),
			array("\\\\", "\\\r", "\\\""), $filename);

		return $filename;
	}

	// }}}
	// {{{ protected function getUriBase()

	protected function getUriBase()
	{
		return 'media';
	}

	// }}}
	// {{{ protected function getFileBase()

	protected function getFileBase()
	{
		if ($this->file_base === null) {
			throw new SiteException('File base has not been set.');
		}

		return $this->file_base;
	}

	// }}}
}

?>
