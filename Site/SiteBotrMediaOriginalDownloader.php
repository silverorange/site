<?php

require_once 'Site/SiteBotrMediaToasterCommandLineApplication.php';

/**
 * Tool used to download all missing originals from bits on the run
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMediaOriginalDownloader
	extends SiteBotrMediaToasterCommandLineApplication
{
	// run phase
	// {{{ public function runInternal()

	protected function runInternal()
	{
		$this->debug("Downloading Originals...\n");

		$originals_to_download = array();

		$media = $this->getMedia();
		foreach ($media as $key => $media_file) {
			if ($this->mediaFileOriginalIsDownloadable($media_file) !== false) {
				$originals_to_download[$key] = $media_file;
			}
		}
		$this->debug(sprintf("%s files on BOTR, %s originals to download.\n\n",
			$this->locale->formatNumber(count($media)),
			$this->locale->formatNumber(count($originals_to_download))));

		$this->downloadOriginals($originals_to_download);
	}

	// }}}
	// {{{ protected function downloadMedia()

	protected function downloadOriginals(array $media)
	{
		$count = 1;

		foreach ($media as $key => $media_file) {
			if (!isset($media_file['custom']['original_filename'])) {
				// log the issue but continue with the downlaods.
				$e = new SiteCommandLineException(sprintf(
					'Original filename missing for media ‘%s’',
					$key));

				$e->processAndContinue();
			} else {
				$filename = $media_file['custom']['original_filename'];
				$info     = pathinfo($filename);

				if (!isset($info['extension'])) {
					// edge case for videos missing their extension on upload.
					// this should never happen, but let us know if it does and
					// still download the file.
					$e = new SiteCommandLineException(sprintf(
						'Original filename ‘%s’ on media ‘%s’ missing '.
							'extension',
						$filename,
						$key));

					$e->processAndContinue();
				}

				// we have to use the passthrough because you can't download
				// the original.
				$passthrough = $this->toaster->getPassthroughByKey($key);

				$this->debug(sprintf("%s - Media: %s ",
					$this->locale->formatNumber($count++),
					$media_file['key']));

				if ($passthrough !== null &&
					$passthrough['status'] == 'Ready') {
					$this->debug(sprintf("(%s) ... ",
						SwatString::byteFormat($passthrough['filesize'])));

					$source = $this->toaster->getMediaDownloadByKeys($key,
						$passthrough['template']['key']);

					$destination = sprintf('%s/%s',
						$this->getDestinationDirectory($key),
						$media_file['custom']['original_filename']);

					try {
						$this->download($source, $destination, $key,
							$passthrough['filesize']);

						// remove the download_pending tag. Original and
						// passthrough deletion happens in the
						// SiteBotrMediaDeleter script
						$this->toaster->updateMediaRemoveTagsByKey($key,
							array($this->original_missing_tag));

						$this->debug("done.\n");
					} catch (Exception $e) {
						$e = new SiteCommandLineException($e);
						$e->processAndContinue();
						$this->debug("error.\n");
					}
				} else {
					$this->debug("passthrough missing - skipping.\n");
				}
			}
		}
	}

	// }}}
	// {{{ protected function getDestinationDirectory()

	protected function getDestinationDirectory($media_key)
	{
		return $this->getSourceDirectory().'/video';
	}

	// }}}
	// {{{ protected function displayResults()

	protected function displayResults()
	{
		// TODO
	}

	// }}}
}

?>
