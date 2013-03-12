<?php

require_once 'Site/SiteBotrMediaToasterCommandLineApplication.php';

/**
 * Deletes video and encodings from BOTR.
 *
 * Videos tagged to delete are deleted after 7 days, and videos that have been
 * validated, and don't have originals to download have any existing original
 * and passthrough encodings deleted.
 *
 * @package   Site
 * @copyright 2011-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */

class SiteBotrMediaDeleter extends SiteBotrMediaToasterCommandLineApplication
{
	// {{{ protected properties

	/**
	 * Number of media files deleted from BOTR.
	 *
	 * @var integer
	 */
	protected $files_deleted_count = 0;
	protected $files_pending_count = 0;
	protected $originals_deleted_count = 0;
	protected $passthroughts_deleted_count = 0;
	protected $originals_already_deleted_count = 0;

	// }}}
	// {{{ public function runInternal()

	protected function runInternal()
	{
		$this->debug("Deleting BOTR Media and Encodings...\n\n");

		$this->deleteMedia();
		$this->deleteEncodings();
		$this->displayResults();
	}

	// }}}
	// {{{ public function deleteMedia()

	protected function deleteMedia()
	{
		$options = array(
			'tags' => $this->delete_tag,
			);

		$media_to_delete = $this->getMedia($options);

		$this->debug(sprintf("Found %s media files to delete.\n",
			$this->locale->formatNumber(count($media_to_delete))));

		if (count($media_to_delete)) {
			foreach ($media_to_delete as $key => $media_file) {
				$delete = true;

				$this->debug(sprintf("Deleting Media ‘%s’ ... ",
					$key));

				// if we have a timestamp, compare it, otherwise just delete.
				if (isset($media_file['custom']['delete_timestamp'])) {
					$threshold = new SwatDate();
					$threshold->toUTC();

					// seriously, there must be a better way to create a new
					// SwatDate from a timestamp
					$delete_date = new SwatDate(
						'@'.$media_file['custom']['delete_timestamp']);

					$delete_date->toUTC();
					$delete_date->addDays(
						$this->config->media->days_to_delete_threshold);

					if ($delete_date->after($threshold)) {
						$delete = false;
					}
				}

				if ($delete) {
					$this->toaster->deleteMediaByKey($key);
					$this->files_deleted_count++;
					$this->debug("done.\n");
				} else {
					$this->files_pending_count++;
					$this->debug(sprintf("skipped ... will be deleted on %s.\n",
						$delete_date->format(SwatDate::DF_DATE_TIME_LONG)));
				}
			}
		}
		$this->debug("\n");
		$this->resetMediaCache();
	}

	// }}}
	// {{{ public function deleteEncodings()

	protected function deleteEncodings()
	{
		$this->debug("Deleting Originals...\n");

		$media = $this->getMedia();
		foreach ($media as $key => $media_file) {
			$this->debug(sprintf("Media ‘%s’ ... ",
				$key));

			if ($this->mediaFileOriginalCanBeDeleted($media_file)) {
				if ($this->mediaFileOriginalIsDeleted($media_file)) {
					$this->debug("original already deleted.");
					$this->originals_already_deleted_count++;
				} else {
					$this->debug("deleting encodings ...");

					$original = $this->toaster->getOriginalByKey($key);
					if ($original !== false) {
						$this->debug(" original ... ");
						$this->toaster->deleteEncodingByKey($original['key']);
						$this->originals_deleted_count++;
					}

					$passthrough = $this->toaster->getPassthroughByKey($key);
					if ($passthrough !== false) {
						$this->debug(" passthrough ... ");
						$this->toaster->deleteEncodingByKey(
							$passthrough['key']);

						$this->passthroughts_deleted_count++;
					}

					$this->toaster->updateMediaAddTagsByKey($key,
						array($this->original_deleted_tag));

					$this->debug("done.");
				}
			} else {
				$this->debug("cannot be deleted.");
			}
			$this->debug("\n");
		}
		$this->debug("\n");
	}

	// }}}
	// {{{ protected function mediaFileOriginalCanBeDeleted()

	protected function mediaFileOriginalCanBeDeleted(array $media_file)
	{
		$deletable = false;

		// can only be deleted if not already deleted, and it is validated,
		// imported and the original isn't missing.
		if ($this->mediaFileIsMarkedValid($media_file) &&
			$this->hasTag($media_file, $this->imported_tag) &&
			!$this->hasTag($media_file, $this->original_missing_tag)) {
			$deletable = true;
		}

		return $deletable;
	}

	// }}}
	// {{{ protected function mediaFileOriginalIsDeleted()

	protected function mediaFileOriginalIsDeleted(array $media_file)
	{
		$deleted = false;

		// can only be deleted if not already deleted, and it is validated,
		// imported and the original isn't missing.
		if ($this->hasTag($media_file, $this->original_deleted_tag)) {
			$deleted = true;
		}

		return $deleted;
	}

	// }}}
	// {{{ protected function displayResults()

	protected function displayResults()
	{
		$total       = count($this->getMedia());
		$not_deleted = $total - $this->originals_deleted_count -
			$this->originals_already_deleted_count;

		$this->debug(sprintf(
			"%s media files found.\n".
			"%s files deleted, %s pending deletion.\n".
			"%s originals already deleted.\n".
			"%s original and %s passthrough encodings deleted.\n".
			"%s originals cannot be deleted.\n\n",
			$this->locale->formatNumber($total),
			$this->locale->formatNumber($this->files_deleted_count),
			$this->locale->formatNumber($this->files_pending_count),
			$this->locale->formatNumber($this->originals_already_deleted_count),
			$this->locale->formatNumber($this->originals_deleted_count),
			$this->locale->formatNumber($this->passthroughts_deleted_count),
			$this->locale->formatNumber($not_deleted)));
	}

	// }}}
}

?>