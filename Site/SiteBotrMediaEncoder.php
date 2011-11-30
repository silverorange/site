<?php

require_once 'Site/SiteBotrMediaToasterCommandLineApplication.php';

/**
 * Encodes all media on BOTR to the default profiles if they haven't already
 * been encoded, as well as optional large profiles. This does nothing to the
 * records in the database.
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      Allow encoding to arbitrary encoding profiles. Also add flag to
 *            call getDistinctDimensions() and getEncodingProfiles() with nicely
 *            returned info (or possibly import the profiles into MediaEncoding.
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
	 * Number of media files with encoding jobs started.
	 *
	 * @var integer
	 */
	protected $media_files_encoding_count = 0;

	/**
	 * Number of encoding jobs started.
	 *
	 * @var integer
	 */
	protected $encoding_jobs_count = 0;

	// }}}
	// {{{ public function runInternal()

	protected function runInternal()
	{
		$this->debug("Starting encoding jobs...\n");
		//$this->getDistinctDimensions();
		$this->getEncodingProfiles();
		$this->startEncodingJobs();
		$this->displayResults();
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
				$dimensions[$key]++;
			} else {
				$dimensions[$key] = 1;
			}
		}

		var_dump($dimensions);
	}

	// }}}
	// {{{ protected function getEncodingProfiles()

	protected function getEncodingProfiles()
	{
		$profiles = $this->toaster->getEncodingProfiles();
		$default_count = 0;

		foreach($profiles as $profile) {
			/*
			 * only check for profiles meant for video and ignore required
			 * profiles as they are always applied on all media. This logic will
			 * have to change as we add audio, or have profiles we want to apply
			 * to both, such as an audio profile that gets used for both video
			 * and audio.
			 */
			if (($profile['default'] == 'video' ||
				$profile['default'] == 'none') &&
				$profile['required'] === false) {
				$this->encoding_profiles[$profile['key']] = $profile;
				if ($profile['default'] == 'video') {
					$default_count++;
				}
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
			// only worry about videos for now.
			if ($media_file['mediatype'] == 'video') {
				$current_encodings = $this->toaster->getEncodingsByKey(
					$media_file['key']);

				$current_keys = array();
				foreach ($current_encodings as $encoding) {
					if ($encoding['template']['format']['key'] == 'original') {
						$original_width = $encoding['width'];
					}

					$current_keys[] = $encoding['template']['key'];
				}

				$new_encodings = 0;
				foreach ($this->encoding_profiles as
					$profile_key => $profile) {
					// don't re-apply, and check to make sure we're not
					// attempting to upscale.
					if (array_search($profile_key, $current_keys) === false &&
						$profile['width'] <= $original_width) {

						// only apply the encoding if its marked as a default,
						// or if it perfectly matches the width of the original.
						if ($profile['default'] == 'video' ||
							$profile['width'] == $original_width) {
							$new_encodings++;
							$this->toaster->encodeMediaByKeys(
								$media_file['key'],
								$profile_key);
						}
					}
				}

				$this->debug(sprintf("Media: %s ... %s current encodings ... ".
					"%s new encodings added.\nFilename: %s\n\n",
					$media_file['key'],
					$this->locale->formatNumber(count($current_keys)),
					$this->locale->formatNumber($new_encodings),
					$media_file['title']));

				if ($new_encodings > 0) {
					$this->media_files_encoding_count++;
					$this->encoding_jobs_count += $new_encodings;
				}
			}
		}
	}

	// }}}
	// {{{ protected function displayResults()

	protected function displayResults()
	{
		$this->debug(sprintf(
			"%s media files found, %s encoding jobs added for %s files.\n\n",
			$this->locale->formatNumber(count($this->getMedia())),
			$this->locale->formatNumber($this->encoding_jobs_count),
			$this->locale->formatNumber($this->media_files_encoding_count)));
	}

	// }}}
}

?>
