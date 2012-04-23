<?php

require_once 'Site/SiteBotrMediaToasterCommandLineApplication.php';

/**
 * Application to validate media on BOTR.
 *
 * Currently validates FTP uploaded media to BOTR.
 *
 * @package   Site
 * @copyright 2011-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaValidator extends SiteBotrMediaToasterCommandLineApplication
{
	// {{{ protected properties

	/**
	 * Array of files which have passed validation.
	 *
	 * @var array
	 */
	protected $valid_files = array();

	/**
	 * Array of filenames and source sizes for files which we can't look up
	 * because of our lame 32bit nature.
	 *
	 * @var array
	 */
	protected $large_file_reference;

	/**
	 * Array of files which have been uploaded more than once.
	 *
	 * @var array
	 */
	protected $duplicate_files = array();

	/**
	 * Array of files which have failed validation.
	 *
	 * @var array
	 */
	protected $failed_files = array();

	/**
	 * Array of files which need their originals downloaded.
	 *
	 * @var array
	 */
	protected $originals_to_download = array();

	// }}}
	// {{{ private properties

	/**
	 * Whether or not to recheck files that have already been marked invalid.
	 *
	 * If true, this will recheck any invalid files.
	 *
	 * @var boolean
	 */
	private $recheck_invalid_files = false;

	/**
	 * Whether or not to automatically mark large (>2GB) files as valid.
	 *
	 * If true, and we are validating by filesize this will set these files as
	 * valid, as PHP cannot get an accurate filesize on a 32bit-OS. Only run
	 * with this flag if you've manually compared the filesizes yourself, and
	 * all files that are large
	 * are correct.
	 *
	 * @var boolean
	 */
	private $mark_large_files_valid = false;

	// }}}

	// {{{ public function __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		$recheck_invalid_files = new SiteCommandLineArgument(
			array('--recheck-invalid-files'),
			'setRecheckInvalidFiles',
			'Optional. Forces previously invalidated files to be rechecked.');

		$this->addCommandLineArgument($recheck_invalid_files);

		$mark_large_files_valid = new SiteCommandLineArgument(
			array('--mark-large-files-valid'),
			'setMarkLargeFilesValid',
			'Optional. Marks large (>2GB) files as valid. Use with caution.');

		$this->addCommandLineArgument($mark_large_files_valid);

		parent::__construct($id, $filename, $title, $documentation);
	}

	// }}}
	// {{{ public function setLargeFileReference()

	public function setLargeFileReference($filename)
	{
		if (file_exists($filename)) {
			if (($handle = fopen($filename, "r")) !== false) {
				while (($line = fgetcsv($handle)) !== false) {
					$this->large_file_reference[$line[0]] = $line[1];
				}
				fclose($handle);
			}
		}
	}

	// }}}
	// {{{ public function setRecheckInvalidFiles()

	public function setRecheckInvalidFiles()
	{
		$this->recheck_invalid_files = true;
	}

	// }}}
	// {{{ public function setMarkLargeFilesValid()

	public function setMarkLargeFilesValid()
	{
		$this->mark_large_files_valid = true;
	}

	// }}}

	// init phase
	// {{{ protected function addSourceFile()

	protected function addSourceFile($key, $path, SplFileInfo $file)
	{
		parent::addSourceFile($key, $path, $file);

		// use the large file reference if its available.
		$info = pathinfo($path);
		if (isset($this->large_file_reference[$info['basename']])) {
			$this->source_files[$key]['size'] =
				$this->large_file_reference[$info['basename']];
		} else {
			$this->source_files[$key]['size'] = $file->getSize();
		}
	}

	// }}}
	// {{{ protected function handleSourceFileException()

	protected function handleSourceFileException($key, $path, SplFileInfo $file,
		Exception $e)
	{
		parent::handleSourceFileException($key, $path, $file, $e);

		$e = new SiteCommandLineException($e);
		if (strpos($e, 'SplFileInfo::getSize(): stat failed') !== false) {
			// don't report the error if we're marking them as valid anyway.
			if (!$this->mark_large_files_valid) {
				$this->failed_files[$key]['path']  = $path;
				$this->failed_files[$key]['error'] =
					'Source $file->getSize() failed.';
			}
		} else {
			$e->processAndContinue();
		}
	}

	// }}}
	// {{{ public function runInternal()

	protected function runInternal()
	{
		$this->debug("Validating media from source files...\n");

		$this->setOriginalFilenames();
		$this->validateUploads();
		$this->displayValidationResults();
	}

	// }}}
	// {{{ protected function getResetTags()

	protected function getResetTags()
	{
		return array(
			$this->valid_tag_filesize,
			$this->valid_tag_md5,
			$this->invalid_tag_filesize,
			$this->invalid_tag_md5,
			$this->duplicate_tag,
			$this->original_missing_tag,
		);
	}

	// }}}
	// {{{ protected function validateUploads()

	protected function validateUploads()
	{
		// TODO: if Botr adds ability to search by negative keywords, search for
		// media that don't have the validated tag. If this happens, we can
		// also get rid of the tag check in the foreach loop over the media.
		$media        = $this->getMedia();
		$source_files = $this->getSourceFiles();
		$tags_to_add  = array();

		$this->debug(sprintf("Found %s media files on BOTR, %s source files.\n",
			$this->locale->formatNumber(count($media)),
			$this->locale->formatNumber(count($source_files))));

		foreach ($media as $media_file) {
			$filename         = $media_file['custom']['original_filename'];
			$valid            = false;
			$dupe             = false;
			$original_missing = false;
			$tags             = array();
			$errors           = array();

			if ($this->mediaFileIsMarkedDeleted($media_file)) {
				// don't bother validating media marked for deletion. Do this
				// before dupes to prevent a deleted video being marked a dupe.
				$valid = true;
			} elseif (array_key_exists($filename, $this->valid_files)) {
				$dupe   = true;
				$tags[] = $this->duplicate_tag;
				if (isset($source_files[$filename])) {
					$this->duplicate_files[$filename][] =
						$source_files[$filename]['path'];
				} else {
					$this->duplicate_files[$filename][] = 'no local copy';
				}
			} elseif ($this->mediaFileIsMarkedValid($media_file)) {
				$valid = true;
			} elseif (!$this->recheck_invalid_files &&
				$this->mediaFileIsMarkedInvalid($media_file)) {
				$valid    = false;
				$errors[] = $media_file['tags'];
			}  elseif ($this->mediaFileIsIgnorable($media_file)) {
				// do nothing. we should make this part of the debugged display
				$valid = true;
			} else {
				if (array_key_exists($filename, $source_files)) {
					$result = $this->validateMediaFile($filename, $media_file);
					$valid  = $result['valid'];
					$errors = $result['errors'];
					$tags   = $result['tags'];
				} else {
					// These are files on Botr that don't have source files
					$original_missing = true;
					$tags[]           = $this->original_missing_tag;
					$this->originals_to_download[$filename] = $media_file;
				}
			}

			if (count($tags)) {
				$tags_to_add[$media_file['key']] = $tags;
			}

			if ($valid === true) {
				$this->valid_files[$filename]['media_file'] = $media_file;

				// don't attempt to add a path we don't have.
				if (array_key_exists($filename, $source_files)) {
					$this->valid_files[$filename]['path'] =
						$source_files[$filename]['path'];
				}
			} elseif ($dupe === false && $original_missing === false) {
				$this->failed_files[$filename]['media_file'] = $media_file;
				$error_message = implode("\n", $errors);
				if (isset($this->failed_files[$filename]['error'])) {
					$this->failed_files[$filename]['error'].=
						"\n".$error_message;
				} else {
					$this->failed_files[$filename]['error'] = $error_message;
				}

				// don't attempt to add a size we don't have.
				if (array_key_exists($filename, $source_files)) {
					$this->failed_files[$filename]['size'] =
						(isset($source_files[$filename]['size'])) ?
						$source_files[$filename]['size'] : '0';
				}
			}
		}

		// check to make sure all source files have been uploaded.
		foreach ($source_files as $filename => $info) {
			if (array_key_exists($filename, $this->valid_files) === false &&
				array_key_exists($filename, $this->failed_files) === false) {
				$this->failed_files[$filename]['path']  = $info['path'];
				$this->failed_files[$filename]['size']  = $info['size'];
				$this->failed_files[$filename]['error'] = sprintf(
					'Source file ‘%s’ not found on BOTR',
					$filename);
			}
		}

		// save any tags to BOTR that we've set above.
		foreach ($tags_to_add as $key => $tags) {
			$this->toaster->updateMediaAddTagsByKey($key, $tags);
		}
	}

	// }}}
	// {{{ protected function validateMediaFile()

	protected function validateMediaFile($filename, array $media_file)
	{
		$result = array(
			'valid'  => false,
			'tags'   => array(),
			'errors' => array(),
		);

		// validate md5 if md5 is set on BOTR. Older videos don't have md5 set.
		if ($media_file['md5'] !== null) {
			if ($this->source_files[$filename]['md5'] == $media_file['md5']) {
				$result['valid']  = true;
				$result['tags'][] = $this->valid_tag_md5;
			} else {
				$result['valid']    = false;
				$result['tags'][]   = $this->invalid_tag_md5;
				$result['errors'][] = sprintf(
					"md5 mismatch. BOTR: %s, Source: %s.\nFilename: %s",
					$media_file['md5'],
					$this->source_files[$filename]['md5'],
					$filename);
			}
		}

		// validate filesize. If the file has already been marked valid by md5
		// and we don't have a source size, don't bother doing the validation.
		$source_size = (isset($this->source_files[$filename]['size'])) ?
			$this->source_files[$filename]['size'] : 0;

		if ($source_size > 0 ||
			$result['valid'] === false ||
			$this->mark_large_files_valid) {
			$original = $this->toaster->getOriginalByKey($media_file['key']);
			if (($source_size == $original['filesize']) ||
				($source_size == 0 && $this->mark_large_files_valid)) {
				$result['valid']  = true;
				$result['tags'][] = $this->valid_tag_filesize;
			} else {
				$result['valid']    = true;
				$result['tags'][]   = $this->invalid_tag_filesize;
				$result['errors'][] = sprintf(
					"Size mismatch. BOTR: %s bytes, Source: %s bytes.\n".
					"Filename: %s",
					$original['filesize'],
					$source_size,
					$filename);
			}
		}

		if (count($result['errors'])) {
			$e = new SiteCommandLineException(sprintf(
				"Media %s validation error:\n%s",
				$media_file['key'],
				implode("\n", $result['errors'])));

			$e->processAndContinue();
		}

		return $result;
	}

	// }}}
	// {{{ protected function displayValidationResults()

	protected function displayValidationResults()
	{
		$this->debug(sprintf("%s valid media files (%s originals to ".
			"download), %s duplicates and %s failed validation.\n",
			$this->locale->formatNumber(count($this->valid_files)),
			$this->locale->formatNumber(count($this->originals_to_download)),
			$this->locale->formatNumber(count($this->duplicate_files)),
			$this->locale->formatNumber(count($this->failed_files))));

		if (count($this->duplicate_files) > 0) {
			$this->debug("\nDuplicates:\n");
			foreach ($this->duplicate_files as $filename => $paths) {
				$this->debug(sprintf("%s:\n%s\n",
					$filename,
					implode("\n", $paths)));
			}
		}

		if (count($this->failed_files)) {
			$this->debug("\nFailed:\n");
			foreach ($this->failed_files as $filename => $info) {
				$path = (array_key_exists('path', $info)) ?
					$info['path'] : 'n/a';

				$size = (array_key_exists('size', $info)) ?
					$info['size'].' bytes' : 'n/a';

				$this->debug(sprintf(
					"File: %s\nPath: %s\nSize: %s\nError: %s\n\n",
					$filename,
					$path,
					$size,
					$info['error']));
			}
		}

		$this->debug("\n");
	}

	// }}}
}

?>
