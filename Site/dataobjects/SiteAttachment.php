<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteAttachmentSet.php';
require_once 'Site/dataobjects/SiteAttachmentCdnTask.php';

/**
 * An attachment
 *
 * @package   Site
 * @copyright 2011 silverorange
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
	 * @var Date
	 */
	public $createdate;

	// }}}
	// {{{ protected properties

	/**
	 * TODO
	 * @var string
	 */
	protected $attachment_set_shortname;

	// }}}
	// {{{ private properties

	/**
	 * TODO
	 * @var string
	 */
	private $file_base;

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
	// {{{ public function getFormattedFileSize()

	public function getFormattedFileSize()
	{
		return SwatString::byteFormat($this->file_size, -1, false, 1);
	}

	// }}}
	// {{{ public function getExtension()

	public function getExtension()
	{
		switch ($this->mime_type) {
		case 'video/mp4':
			return 'mp4';
		case 'audio/mp4':
			return 'mp4';
		case 'audio/mpeg':
			return 'mp3';
		case 'application/zip':
			return 'zip';
		case 'application/pdf':
			return 'pdf';
		default:
			throw new SiteException(sprintf(
				'Unknown mime type %s', $this->mime_type));
		}
	}

	// }}}
	// {{{ public function getHumanFileType()

	public function getHumanFileType()
	{
		switch ($this->mime_type) {
		case 'video/mp4':       return 'MP4';
		case 'audio/mp4':       return 'MP4';
		case 'audio/mpeg':      return 'MP3';
		case 'application/zip': return 'Zip';
		case 'application/pdf': return 'PDF';
		default:
			throw new SiteException(sprintf(
				'Unknown mime type %s', $this->mime_type));
		}
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

	public function getUri()
	{
		$uri = $this->getUriSuffix();

		if ($this->on_cdn && self::$cdn_base != '') {
			$uri = self::$cdn_base.$uri;
		} else if ($prefix != '' && !strpos($uri, '://')) {
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
		return sprintf('%s.%s', $this->id, $this->getExtension());
	}

	// }}}
	// {{{ public function getContentDispositionFilename()

	public function getContentDispositionFilename()
	{
		// Convert to an ASCII string. Approximate non ACSII characters.
		$filename = iconv('UTF-8', 'ASCII//TRANSLIT', $this->original_filename);

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
		try {
			$transaction = new SwatDBTransaction($this->db);

			$directory = $this->getFileDirectory();
			if (!file_exists($directory) && !mkdir($directory, 0777, true)) {
				throw new SiteException('Unable to create directory.');
			}

			$this->save();

			if ($this->getAttachmentSet()->use_cdn) {
				$this->queueCdnTask(SiteCdnTask::COPY_OPERATION);
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

		if ($this->attachment_set_shortname == '') {
			throw new SiteException('To process attachment, a '.
				'SiteAttachmentType shortname must be defined in the '.
				'SiteAttachment dataobject.');
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
			$this->queueCdnTask(SiteCdnTask::DELETE_OPERATION);
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
		$class_name = SwatDBClassMap::get('SiteAttachmentCdnTask');
		$task = new $class_name();
		$task->setDatabase($this->db);
		$task->operation = $operation;

		if ($operation == SiteCdnTask::COPY_OPERATION) {
			$task->attachment = $this;
		} else {
			$task->file_path = $this->getUriSuffix();
		}

		$task->save();
	}

	// }}}
}

?>
