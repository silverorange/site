<?php

require_once 'Site/SiteBotrMediaToasterCommandLineApplication.php';

/**
 * Encodes all media on BOTR to the default profiles if they haven't already
 * been encoded, as well as optional large profiles. This does nothing to the
 * records in the database.
 *
 * @package   Site
 * @copyright 2011-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      Allow encoding to arbitrary encoding profiles. Also add flag to
 *            call getDistinctDimensions() and getEncodingProfiles() with nicely
 *            returned info (or possibly import the profiles into MediaEncoding.
 *            Allow setting audio encodings only on certain media by tag.
 */

class SiteBotrMediaEncoder extends SiteBotrMediaToasterCommandLineApplication
{
	// {{{ protected properties

	/**
	 * Array of encoding profiles available to use.
	 *
	 * @var array
	 */
	protected $encoding_profiles = array();

	/**
	 * Key of the passthrough profile.
	 *
	 * A special case profile only used when originals are missing. This then
	 * needs to be encoded so that we can download the original (as the original
	 * encoding is frustratingly unavailable for download).
	 *
	 * @var string
	 */
	protected $passthrough_profile_key;

	/**
	 * Number of media files with all encodings complete.
	 *
	 * @var integer
	 */
	protected $complete_files = 0;

	/**
	 * Number of media files marked invalid.
	 *
	 * @var integer
	 */
	protected $invalid_files = 0;

	/**
	 * Number of media files with encoding currently running.
	 *
	 * @var integer
	 */
	protected $current_media_files_encoding_count = 0;

	/**
	 * Number of media files with encoding jobs started.
	 *
	 * @var integer
	 */
	protected $new_media_files_encoding_count = 0;

	/**
	 * Number of encoding jobs started.
	 *
	 * @var integer
	 */
	protected $new_encoding_jobs_count = 0;

	/**
	 * Number of encoding jobs currently running.
	 *
	 * @var integer
	 */
	protected $current_encoding_jobs_count = 0;

	/**
	 * Number of new passthrough encodings added.
	 *
	 * @var integer
	 */
	protected $new_passthrough_encoding_jobs = 0;

	// }}}
	// {{{ public function runInternal()

	protected function runInternal()
	{
		$this->debug("Starting encoding jobs...\n");
		//$this->resetTitles();
		//$this->getDistinctDimensions();
		$this->setOriginalFilenames();
		$this->getEncodingProfiles();
		$this->startEncodingJobs();
		$this->displayResults();
	}

	// }}}
	// {{{ protected function getResetTags()

	protected function getResetTags()
	{
		return array(
			$this->encoded_tag,
		);
	}

	// }}}
	// {{{ public function getDistinctDimensions()

	protected function getDistinctDimensions()
	{
		// TODO, set this up somewhere in a less hacky manner
		$dimensions = array();
		foreach ($this->getMedia() as $media_file) {
			$original = $this->toaster->getOriginalByKey($media_file['key']);
			$key = $original['width'].'x'.$original['height'];
			if (isset($dimensions[$key])) {
				$dimensions[$key]['count']++;
			} else {
				$dimensions[$key]['count'] = 1;
				$dimensions[$key]['ratio'] = $original['width'] /
					$original['height'];
			}
		}

		var_dump($dimensions);
	}

	// }}}
	// {{{ protected function resetTitles()

	protected function resetTitles()
	{
		$media = $this->getMedia();
		$count = 0;

		$this->debug('Resetting titles to original filename... ');

		foreach ($media as $media_file) {
			if (isset($media_file['custom']['original_filename']) &&
				$media_file['title'] == '') {
				$info  = pathinfo($media_file['custom']['original_filename']);
				$title = $info['filename'];

				$count++;
				// save fields on Botr.
				$values = array(
					'title' => $title,
					);

				$this->toaster->updateMediaByKey($media_file['key'], $values);
			}
		}

		$this->debug(sprintf("%s titles reset.\n",
			$this->locale->formatNumber($count)));
	}

	// }}}
	// {{{ protected function getEncodingProfiles()

	protected function getEncodingProfiles()
	{
		$profiles = $this->toaster->getEncodingProfiles();
		$default_count = 0;

		foreach($profiles as $profile) {
			/*
			 * Exclude profiles meant for audio files only, and required
			 * profiles that BOTR always builds. Audio only processing isn't
			 * supported.
			 */
			if ($profile['default'] != 'audio' &&
				$profile['required'] === false) {
				$this->encoding_profiles[$profile['key']] = $profile;
				if ($profile['default'] != 'none') {
					$default_count++;
				}
			}

			if ($profile['format']['key'] == 'passthrough') {
				$this->passthrough_profile_key = $profile['key'];
			}
		}

		$optional_count = count($this->encoding_profiles) - $default_count;
		$this->debug(sprintf(
			"Found %s encoding profiles, %s required, %s optional...\n",
			$this->locale->formatNumber(count($this->encoding_profiles)),
			$this->locale->formatNumber($default_count),
			$this->locale->formatNumber($optional_count)));
	}

	// }}}
	// {{{ protected function startEncodingJobs()

	protected function startEncodingJobs()
	{
		$media = $this->getMedia();

		$this->debug(sprintf("Found %s media files on BOTR...\n\n",
			$this->locale->formatNumber(count($media))));

		// check all media for encodings they are missing, and if missing, start
		// the encode job.
		foreach ($media as $media_file) {
			// special case for originals missing download that runs
			// independently. Only make the passthrough if the video is tagged
			// as original missing, no passthrough already exists and the
			// original still exists on BOTR
			if ($this->mediaFileOriginalIsDownloadable($media_file)) {
				if ($this->toaster->getPassthroughByKey($media_file['key']) ===
					false &&
					$this->toaster->getOriginalByKey($media_file['key']) !==
					false) {

					try {
						$this->toaster->encodeMediaByKeys($media_file['key'],
							$this->passthrough_profile_key);

						$this->new_passthrough_encoding_jobs++;
					} catch (SiteBotrMediaToasterException $e) {
						$e->processAndContinue();
					}
				}
			}

			if ($this->mediaFileIsMarkedInvalid($media_file) ||
				$this->mediaFileIsIgnorable($media_file)) {
				// don't bother checking for valid files, as files with
				// originals still to download aren't marked valid and files
				// marked ignorable aren't to be encoded.
				$this->invalid_files++;
			} elseif ($this->mediaFileIsMarkedEncoded($media_file)) {
				$this->complete_files++;
			} elseif ($media_file['mediatype'] == 'video') {
				// We only support video files.
				$key               = $media_file['key'];
				$current_keys      = array();
				$current_encodings = $this->toaster->getEncodingsByKey($key);
				$new_jobs          = 0;
				$current_jobs      = 0;
				$complete_jobs     = 0;
				$failed_jobs       = 0;
				$complete          = true;

				foreach ($current_encodings as $encoding) {
					switch ($encoding['status']) {
					case 'Waiting for original':
					case 'Queued':
					case 'Encoding':
					case 'Re-encoding':
					case 'Uploading to CDN':
						$current_jobs++;
						$complete = false;
						break;

					case 'Ready':
						$complete_jobs++;
						break;

					case 'Failed':
					case 'Upload to CDN failed':
						$failed_jobs++;
						$complete = false;
						$e = new SiteCommandLineException(sprintf('Media %s '.
							'returned failed status for %s encoding %s',
							$key,
							$encoding['template']['format']['name'],
							$encoding['key']));

						$e->processAndContinue();
						break;

					default:
						$complete = false;
						$e = new SiteCommandLineException(sprintf('Media %s '.
							'returned unknown status ‘%s’ for %s encoding %s',
							$key,
							$encoding['status'],
							$encoding['template']['format']['name'],
							$encoding['key']));

						$e->processAndContinue();
						break;
					}

					if ($encoding['template']['format']['key'] == 'original') {
						$original_width = $encoding['width'];
					}

					$current_keys[] = $encoding['template']['key'];
				}

				foreach ($this->encoding_profiles as
					$profile_key => $profile) {
					// don't re-apply, and check to make sure we're not
					// attempting to upscale.
					if (array_search($profile_key, $current_keys) === false &&
						$profile['width'] <= $original_width) {
						// only apply the encoding if its marked as a default,
						// or if it perfectly matches the width of the original.
						if ($profile['default'] == 'video' ||
							$profile['default'] == 'all' ||
							$profile['width'] == $original_width) {
							$new_jobs++;
							$complete = false;
							$this->toaster->encodeMediaByKeys(
								$key,
								$profile_key);
						}
					}
				}

				$this->debug(sprintf("Media: %s ... %s complete encodings ... ".
					"%s existing encoding jobs, %s new encodings jobs added.\n".
					"%s failed encoding jobs.\n".
					"Filename: %s\n\n",
					$key,
					$this->locale->formatNumber($complete_jobs),
					$this->locale->formatNumber($current_jobs),
					$this->locale->formatNumber($new_jobs),
					$this->locale->formatNumber($failed_jobs),
					$media_file['title']));

				if ($new_jobs > 0) {
					$this->new_media_files_encoding_count++;
					$this->new_encoding_jobs_count += $new_jobs;
				}

				if ($current_jobs > 0) {
					$this->current_media_files_encoding_count++;
					$this->current_encoding_jobs_count += $current_jobs;
				}

				// mark as encoded.
				if ($complete) {
					$this->complete_files++;
					$this->toaster->updateMediaAddTagsByKey(
						$key,
						array($this->encoded_tag));
				}
			}
		}
	}

	// }}}
	// {{{ protected function displayResults()

	protected function displayResults()
	{
		$this->debug(sprintf(
			"%s media files found.\n".
			"%s invalid files, %s completely encoded.\n".
			"%s passthrough encoding jobs added.\n".
			"%s existing jobs for %s files.\n".
			"%s encoding jobs added for %s files.\n\n",
			$this->locale->formatNumber(count($this->getMedia())),
			$this->locale->formatNumber($this->invalid_files),
			$this->locale->formatNumber($this->complete_files),
			$this->locale->formatNumber($this->new_passthrough_encoding_jobs),
			$this->locale->formatNumber($this->current_encoding_jobs_count),
			$this->locale->formatNumber(
				$this->current_media_files_encoding_count),
			$this->locale->formatNumber($this->new_encoding_jobs_count),
			$this->locale->formatNumber(
				$this->new_media_files_encoding_count)));
	}

	// }}}
	// {{{ protected function getDefaultMediaOptions()

	protected function getDefaultMediaOptions()
	{
		// override status filter in parent
		return array();
	}

	// }}}
}

?>
