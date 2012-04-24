<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteAttachmentSet.php';
require_once 'Site/dataobjects/SiteAttachmentCdnTask.php';

/**
 * An attachment
 *
 * @package   Site
 * @copyright 2011-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      Delete local files.
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
	 * @var string
	 */
	public $original_filename;

	/**
	 * CDN filename if obfuscated filenames are used for this attachment's
	 * attachment set
	 *
	 * @var string
	 */
	public $filename;

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
			'audio/mp4'          => 'm4a',
			'video/mp4'          => 'mp4',
			'audio/mpeg'         => 'mp3',
			'application/zip'    => 'zip',
			'application/pdf'    => 'pdf',
			'image/jpeg'         => 'jpg',
			'application/msword' => 'doc',
			'text/html'          => 'html',
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
			'audio/mp4'          => Site::_('M4A'),
			'video/mp4'          => Site::_('MP4'),
			'audio/mpeg'         => Site::_('MP3'),
			'application/zip'    => Site::_('ZIP'),
			'application/pdf'    => Site::_('PDF'),
			'image/jpeg'         => Site::_('JPEG Image'),
			'application/msword' => Site::_('Word Document'),
			'text/html'          => Site::_('Web Document'),
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
			$prefix = $this->filename;
		} else {
			$prefix = $this->id;
		}

		return sprintf('%s.%s', $prefix, $this->getExtension());
	}

	// }}}
	// {{{ public function getContentDispositionFilename()

	public function getContentDispositionFilename()
	{
		// Convert to an ASCII string. Approximate non ACSII characters.
		$filename = iconv(
			'UTF-8', 'ASCII//TRANSLIT',
			($this->filename != '') ?
				$this->filename :
				$this->original_filename
		);

		// Format the filename according to the qtext syntax in RFC 822
		$filename = str_replace(array("\\", "\r", "\""),
			array("\\\\", "\\\r", "\\\""), $filename);

		return $filename;
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

			$directory = $this->getFileDirectory();
			if (!file_exists($directory) && !mkdir($directory, 0777, true)) {
				throw new SiteException('Unable to create directory.');
			}

			if ($this->getAttachmentSet()->obfuscate_filename) {
				$this->filename = sha1(uniqid(rand(), true));
			}

			$this->save();

			if ($this->getAttachmentSet()->use_cdn) {
				$this->queueCdnTask('copy');
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

		parent::deleteInternal();
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

		if ($operation == 'copy') {
			$task->attachment = $this;
			$task->override_http_headers = serialize(
				array(
					'content-disposition' => sprintf(
						'attachment; filename="%s"',
						$this->getContentDispositionFilename()
					),
				)
			);
		} else {
			$task->file_path = $this->getUriSuffix();
		}

		$task->save();
	}

	// }}}
}

?>
