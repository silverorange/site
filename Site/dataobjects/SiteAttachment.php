<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteAttachmentSet.php';
require_once 'Site/dataobjects/SiteAttachmentCdnTask.php';

/**
 * An attachment
 *
 * @package   Site
 * @copyright 2011-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAttachment extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * The unique identifier of this attachment
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The obfuscated unique identifier of this attachment
	 *
	 * @var string
	 */
	public $obfuscated_id;

	/**
	 * The title of this attachment.
	 *
	 * Title is also used for ordering attachments.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Original filename
	 *
	 * When serving the file this filename is second choice for the
	 * Content-Disposition HTTP header.
	 *
	 * @var string
	 */
	public $original_filename;

	/**
	 * The optional, human friendly, filename of this attachment
	 *
	 * When serving the file this filename is first choice for the
	 * Content-Disposition HTTP header.
	 *
	 * @var string
	 */
	public $human_filename;

	/**
	 * Mime type
	 *
	 * @var string
	 */
	public $mime_type;

	/**
	 * File size of the attachment in bytes.
	 *
	 * Database field in numeric(10,0) since our systems can't support bigint
	 * due to be 32bit.
	 *
	 * @var float
	 */
	public $file_size;

	/**
	 * Whether or not this attachment has been copied to the CDN
	 *
	 * @var boolean
	 */
	public $on_cdn;

	/**
	 * The date that this attachment was created
	 *
	 * @var SwatDate
	 */
	public $createdate;

	// }}}
	// {{{ protected properties

	/**
	 * @var string
	 *
	 * @see SiteAttachment::setCDNBase()
	 */
	protected static $cdn_base;

	/**
	 * TODO
	 * @var string
	 */
	protected $attachment_set_shortname;

	/**
	 * TODO
	 * @var string
	 */
	protected $file_base;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Attachment';
		$this->id_field = 'integer:id';

		$this->registerDateProperty('createdate');

		$this->registerInternalProperty('attachment_set',
			SwatDBClassMap::get('SiteAttachmentSet'));
	}

	// }}}
	// {{{ public static function setCDNBase()

	public static function setCDNBase($base)
	{
		self::$cdn_base = $base;
	}

	// }}}
	// {{{ public function getFormattedFileSize()

	public function getFormattedFileSize()
	{
		return SwatString::byteFormat($this->file_size, -1, false, 1);
	}

	// }}}
	// {{{ public function getExtension()

	/**
	 * Returns the extension of the attachment based on  mime type.
	 *
	 * For MPEG-4 Audio, we use the non-standard but universally accepted m4a
	 * extension. See wikipedia for more details
	 * {@link http://en.wikipedia.org/wiki/.m4a}
	 *
	 * @returns string The extension of the file.
	 */
	public function getExtension()
	{
		$map = array(
			'audio/mp4'                => 'm4a',
			'video/mp4'                => 'mp4',
			'audio/mpeg'               => 'mp3',
			'application/zip'          => 'zip',
			'application/pdf'          => 'pdf',
			'image/jpeg'               => 'jpg',
			'application/msword'       => 'doc',
			'text/html'                => 'html',
			'application/vnd.ms-excel' => 'xls',
			'application/vnd.openxmlformats-officedocument'.
				'.wordprocessingml.document' => 'xlsx',
		);

		if (!array_key_exists($this->mime_type, $map)) {
			throw new SiteException(sprintf(
				'Unknown mime type %s', $this->mime_type));
		}

		return $map[$this->mime_type];
	}

	// }}}
	// {{{ public function getHumanFileType()

	public function getHumanFileType()
	{
		$map = array(
			'audio/mp4'                => Site::_('M4A'),
			'video/mp4'                => Site::_('MP4'),
			'audio/mpeg'               => Site::_('MP3'),
			'application/zip'          => Site::_('ZIP'),
			'application/pdf'          => Site::_('PDF'),
			'image/jpeg'               => Site::_('JPEG Image'),
			'application/msword'       => Site::_('Word Document'),
			'text/html'                => Site::_('Web Document'),
			'application/vnd.ms-excel' => Site::_('Excel Document'),
			'application/vnd.openxmlformats-officedocument'.
				'.wordprocessingml.document' => Site::_('Excel Document'),
		);

		if (!array_key_exists($this->mime_type, $map)) {
			throw new SiteException(sprintf(
				'Unknown mime type %s', $this->mime_type));
		}

		return $map[$this->mime_type];
	}

	// }}}
	// {{{ public function getDownloadUri()

	public function getDownloadUri($prefix = '')
	{
		if (strlen($prefix) > 0)
			$prefix.= '/';

		return sprintf('%sattachment%s', $prefix, $this->id);
	}

	// }}}
	// {{{ public function getUri()

	public function getUri($prefix = '')
	{
		$uri = $this->getUriSuffix();

		if ($this->on_cdn && self::$cdn_base != '') {
			$uri = self::$cdn_base.$uri;
		} elseif ($prefix != '' && !strpos($uri, '://')) {
			$uri = $prefix.$uri;
		}

		return $uri;
	}

	// }}}
	// {{{ public function getUriSuffix()

	public function getUriSuffix()
	{
		$suffix = sprintf('%s/%s',
			$this->getAttachmentSet()->shortname,
			$this->getFilename());

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

	public function getFileDirectory()
	{
		$items = array(
			$this->getFileBase(),
			$this->getAttachmentSet()->shortname,
		);

		return implode(DIRECTORY_SEPARATOR, $items);
	}

	// }}}
	// {{{ public function getFilePath()

	public function getFilePath()
	{
		$items = array($this->getFileDirectory(), $this->getFilename());

		return implode(DIRECTORY_SEPARATOR, $items);
	}

	// }}}
	// {{{ public function getFilename()

	public function getFilename()
	{
		if ($this->getAttachmentSet()->obfuscate_filename) {
			$prefix = $this->obfuscated_id;
		} else {
			$prefix = $this->id;
		}

		return sprintf('%s.%s', $prefix, $this->getExtension());
	}

	// }}}
	// {{{ public function getHttpHeaders()

	public function getHttpHeaders()
	{
		$headers = array();

		// Set a "never-expire" policy with a far future max age (10 years) as
		// suggested http://developer.yahoo.com/performance/rules.html#expires.
		// As well, set Cache-Control to public, as this allows some browsers to
		// cache the images to disk while on https, which is a good win. This
		// depends on setting new object ids when updating the object, if this
		// isn't true of a subclass this will have to be overwritten.
		$headers['Cache-Control'] = 'public, max-age=315360000';

		$headers['Content-Type'] = $this->mime_type;
		$headers['Content-Length'] = $this->file_size;

		// Convert to an ASCII string. Approximate non ACSII characters.
		$filename = iconv(
			'UTF-8', 'ASCII//TRANSLIT',
			($this->human_filename != '') ?
				$this->human_filename :
				$this->original_filename
		);

		// Format the filename according to the qtext syntax in RFC 822
		$filename = str_replace(array("\\", "\r", "\""),
			array("\\\\", "\\\r", "\\\""), $filename);

		$headers['Content-Disposition'] = sprintf(
			'attachment; filename="%s"',
			$filename
		);

		return $headers;
	}

	// }}}
	// {{{ public function load()

	public function load($id)
	{
		$loaded = parent::load($id);

		if ($loaded && $this->attachment_set_shortname !== null) {
			if ($this->attachment_set->shortname !==
				$this->attachment_set_shortname)
				throw new SiteException('Trying to load attachment with the '.
					'wrong attachment set. This may happen if the wrong '.
					'wrapper class is used.');
		}

		return $loaded;
	}

	// }}}
	// {{{ public function process()

	public function process($file_path)
	{
		$this->checkDB();

		try {
			$transaction = new SwatDBTransaction($this->db);

			if ($this->getAttachmentSet()->obfuscate_filename) {
				$this->obfuscated_id = sha1(uniqid(rand(), true));
			}

			$this->save();

			if ($this->getAttachmentSet()->use_cdn) {
				$this->queueCdnTask('copy');
			}

			$directory = $this->getFileDirectory();
			if (!file_exists($directory) && !mkdir($directory, 0777, true)) {
				throw new SiteException('Unable to create directory.');
			}

			if (!copy($file_path, $this->getFilePath())) {
				throw new SiteException('Unable to copy attachment.');
			}

			$transaction->commit();
		} catch (Exception $e) {
			throw $e;
			$transaction->rollback();
		}
	}

	// }}}
	// {{{ protected function getAttachmentSet()

	protected function getAttachmentSet()
	{
		if ($this->attachment_set instanceof SiteAttachmentSet) {
			return $this->attachment_set;
		}

		$this->checkDB();

		if ($this->attachment_set_shortname == '') {
			throw new SiteException('To process this attachment, a '.
				'SiteAttachmentType shortname must be set for the '.
				'$attachment_set_shortname property of this object. Usually '.
				'a default value is set in the class definition.');
		}

		$class_name = SwatDBClassMap::get('SiteAttachmentSet');
		$attachment_set = new $class_name();
		$attachment_set->setDatabase($this->db);

		if (!$attachment_set->loadByShortname(
			$this->attachment_set_shortname)) {
			throw new SiteException(sprintf(
				'Attachment set “%s” does not exist.',
					$this->attachment_set_shortname));
		}

		$this->attachment_set = $attachment_set;

		return $this->attachment_set;
	}

	// }}}
	// {{{ protected function getUriBase()

	protected function getUriBase()
	{
		return 'attachments';
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
	// {{{ protected function deleteInternal()

	protected function deleteInternal()
	{
		if ($this->on_cdn) {
			$this->queueCdnTask('delete');
		}

		$local_file = $this->getFilePath();

		parent::deleteInternal();

		if (file_exists($local_file)) {
			unlink($local_file);
		}
	}

	// }}}
	// {{{ protected function queueCdnTask()

	/**
	 * Queues a CDN task to be preformed later
	 *
	 * @param string $operation the operation to preform
	 */
	protected function queueCdnTask($operation)
	{
		$this->checkDB();

		$class_name = SwatDBClassMap::get('SiteAttachmentCdnTask');
		$task = new $class_name();
		$task->setDatabase($this->db);
		$task->operation = $operation;

		if (($operation == 'copy') || ($operation == 'update')) {
			$task->attachment = $this;
		} else {
			$task->file_path = $this->getUriSuffix();
		}

		$task->save();
	}

	// }}}
}

?>
