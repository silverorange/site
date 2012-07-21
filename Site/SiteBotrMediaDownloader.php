<?php

require_once 'Site/SiteBotrMediaToasterCommandLineApplication.php';
require_once 'Site/dataobjects/SiteBotrMediaWrapper.php';
require_once 'Site/dataobjects/SiteMediaCdnTask.php';

/**
 * Tool used to duplicate all downloadable videos onto our local filesystem.
 * These videos are then queued for uploading to S3.
 *
 * @package   Site
 * @copyright 2011-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      When we upgrade to a version of PHP that supports the curl option
 *            CURLOPT_MAX_RECV_SPEED_LARGE (>= PHP 5.4), switch to curl for the
 *            download so we can control bandwidth usage. 2 GB+ filesize support
 *            when validating downloads.
 */
class SiteBotrMediaDownloader extends SiteBotrMediaToasterCommandLineApplication
{
	// {{{ protected properties

	/**
	 * The directory containing the media hierarchy
	 *
	 * @var string
	 */
	protected $file_base;

	/**
	 * Shortname of the MediaSet to limit downloading to.
	 *
	 * @var string
	 */
	protected $media_set_shortname;

	/**
	 * Shortnames of the SiteBotrMediaDimensions to download.
	 *
	 * @var array
	 */
	protected $download_dimension_shortnames = array();

	/**
	 * List of encodings downloaded for each media entry. Used for debug
	 * messages.
	 *
	 * @var array
	 */
	protected $encodings_downloaded = array();

	/**
	 * Whether or not to re-queue all media cdn upload tasks.
	 *
	 * If true, this will queue all downloadable media, not just media that
	 * hasn't been downloaded before
	 *
	 * @var boolean
	 */
	protected $force_cdn_upload = false;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		parent::__construct($id, $filename, $title, $documentation);

		$force_cdn_upload = new SiteCommandLineArgument(
			array('--force-cdn-upload'),
			'setForceCdnUpload',
			'Optional. Re-queues all downloadable media.');

		$this->addCommandLineArgument($force_cdn_upload);

		$this->initModules();
		$this->parseCommandLineArguments();

		$this->locale = SwatI18NLocale::get();
	}

	// }}}
	// {{{ public function setFileBase()

	public function setFileBase($file_base)
	{
		$this->file_base = $file_base;
	}

	// }}}

	// {{{ public function setMediaSet()

	public function setMediaSet($media_set_shortname)
	{
		$this->media_set_shortname = $media_set_shortname;

		// reset any cached media objects.
		$this->media_objects = null;
	}

	// }}}
	// {{{ public function addDownloadDimension()

	public function addDownloadDimension($shortname)
	{
		$this->download_dimension_shortnames[] = $shortname;
	}

	// }}}
	// {{{ public function setForceCdnUpload()

	public function setForceCdnUpload()
	{
		$this->force_cdn_upload = true;
	}

	// }}}

	// run phase
	// {{{ public function runInternal()

	protected function runInternal()
	{
		$this->debug(sprintf("Downloading %s Media...\n",
			$this->media_set_shortname));

		$media = $this->getMediaObjects();

		if (count($media)) {
			$message = '%s Media to check, ';
			if (count($this->download_dimension_shortnames)) {
				$message.= '%s dimensions to download.';
			} else {
				$message.= 'downloading all dimensions.';
			}

			$this->debug(sprintf($message."\n\n",
				$this->locale->formatNumber(count($media)),
				$this->locale->formatNumber(
					count($this->download_dimension_shortnames))));

			$this->downloadMedia($media);
		}
	}

	// }}}
	// {{{ protected function downloadMedia()

	protected function downloadMedia(array $media)
	{
		$count = 1;
		foreach ($media as $media_object) {
			$bindings = array();

			$this->debug(sprintf("%s - Media id: %s Key: %s ... \n",
				$this->locale->formatNumber($count++),
				$media_object->id,
				$media_object->key));

			if (count($this->download_dimension_shortnames)) {
				foreach ($this->download_dimension_shortnames as $shortname) {
					// special case for largest and smalled, since there isn't
					// necessarily a consistent dimension across the media for
					// these.
					switch ($shortname) {
					case 'largest':
						$binding =
							$media_object->getLargestVideoEncodingBinding();

						break;

					case 'smallest':
						$binding =
							$media_object->getSmallestVideoEncodingBinding();
						break;

					case 'audio':
						$binding =
							$media_object->getDefaultAudioEncoding();
						break;

					default:
						$binding =
							$media_object->getEncodingBinding($shortname);
						break;
					}

					// index by $binding->media_encoding, which is an id - this
					// means if the largest, smallest or audio is also an
					// explicitly defined shortname in
					// $download_dimension_shortnames we won't attempt to
					// download it twice.
					if ($binding !== null) {
						$bindings[$binding->media_encoding] = $binding;
					}
				}
			} else {
				// fall back to downloading all encodings.
				foreach ($media_object->encoding_bindings as $binding) {
					$bindings[$binding->media_encoding] = $binding;
				}
			}

			foreach ($bindings as $encoding => $binding) {
				if ($this->isDownloadable($media_object, $binding)) {
					$this->downloadAndQueueBinding($media_object, $binding);
				} else {
					$encoding = $media_object->media_set->encodings->getByIndex(
						$binding->media_encoding);

					$this->debug(sprintf(
						"\t“%s” encoding not downloadable.\n",
						$encoding->shortname));
				}
			}

			$this->debug("\n");
		}
	}

	// }}}
	// {{{ protected function isDownloadable()

	protected function isDownloadable(SiteBotrMedia $media_object,
		SiteBotrMediaEncodingBinding $binding)
	{
		return true;
	}

	// }}}
	// {{{ protected function downloadAndQueueBinding()

	protected function downloadAndQueueBinding(SiteBotrMedia $media_object,
		SiteBotrMediaEncodingBinding $binding)
	{
		$encoding = $media_object->media_set->encodings->getByIndex(
			$binding->media_encoding);

		$this->debug(sprintf("\tDownloading “%s” encoding (%s) ... ",
			$encoding->shortname,
			SwatString::byteFormat($binding->filesize)));

		$media_object->setFileBase($this->file_base);
		$file_path = $media_object->getFilePath($encoding->shortname);

		if (file_exists($file_path)) {
			$this->debug("already downloaded.");

			if ($this->force_cdn_upload) {
				if ($this->isQueueable($media_object, $binding)) {
					$this->debug(" Queued for CDN upload.");
					$this->queueCdnTask($media_object, $encoding);
				}
			}

			$this->debug("\n");
		} else {
			try {
				$this->downloadFile($media_object, $binding, $encoding);

				if ($this->isQueueable($media_object, $binding)) {
					$this->queueCdnTask($media_object, $encoding);
				}

				$this->debug("done.\n");
			} catch (SwatException $e) {
				$e->processAndContinue();
				$this->debug("error.\n");
			}
		}
	}

	// }}}
	// {{{ protected function isQueueable()

	protected function isQueueable(SiteBotrMedia $media_object,
		SiteBotrMediaEncodingBinding $binding)
	{
		// make sure the media set is supposed to be on the cdn.
		return ($media_object->media_set->use_cdn);
	}

	// }}}
	// {{{ protected function downloadFile()

	protected function downloadFile(SiteBotrMedia $media_object,
		SiteBotrMediaEncodingBinding $binding,
		SiteBotrMediaEncoding $encoding)
	{
		$destination = $media_object->getFilePath($encoding->shortname);
		$prefix      = $media_object->id;
		$filesize    = $binding->filesize;
		$source      = $this->toaster->getMediaDownload($media_object,
			$encoding);

		$this->debug(sprintf("\n\t => %s ... ",
			$destination));

		$this->download($source, $destination, $prefix, $filesize);
	}

	// }}}
	// {{{ protected function queueCdnTask()

	protected function queueCdnTask(SiteBotrMedia $media,
		SiteBotrMediaEncoding $encoding)
	{
		$class_name = SwatDBClassMap::get('SiteMediaCdnTask');

		$task = new $class_name();
		$task->setDatabase($this->db);

		$task->media     = $media;
		$task->encoding  = $encoding;
		$task->operation = 'copy';
		$task->override_http_headers = serialize(array(
			'Content-Disposition' => sprintf('attachment; filename="%s"',
				$media->getContentDispositionFilename($encoding->shortname)),
			));

		$task->save();
	}

	// }}}
	// {{{ protected function getMediaObjectWhere()

	protected function getMediaObjectWhere()
	{
		$where = parent::getMediaObjectWhere();

		$where.= sprintf(' and Media.downloadable = %s',
			$this->db->quote(true, 'boolean'));

		if ($this->media_set_shortname !== null) {
			$where.= sprintf(' and MediaSet.shortname = %s',
				$this->db->quote($this->media_set_shortname, 'text'));
		}

		return $where;
	}

	// }}}
	// {{{ protected function getMediaObjectJoin()

	protected function getMediaObjectJoin()
	{
		$join = null;

		if ($this->media_set_shortname !== null) {
			$join = 'inner join MediaSet on MediaSet.id = Media.media_set';
		}

		return $join;
	}

	// }}}
	// {{{ protected function displayResults()

	protected function displayResults()
	{
		$this->debug(sprintf(
			"%s media dataobjects found, %s files already imported.\n".
			"%s to recheck, %s new bindings added.\n".
			"%s ready to import, %s successfully imported.\n\n",
			$this->locale->formatNumber(count($this->getMediaObjects())),
			$this->locale->formatNumber($this->already_imported_count),
			$this->locale->formatNumber(count($this->media_to_recheck)),
			$this->locale->formatNumber($this->encodings_added_count),
			$this->locale->formatNumber(count($this->media_to_import)),
			$this->locale->formatNumber($this->imported_count)));
	}

	// }}}
}

?>
