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
			if ($this->mediaFileOriginalIsDownloadable($media_file)) {
				$originals_to_download[$key] = $media_file;
			}
		}

		$this->debug(sprintf("%s files on BOTR, %s originals to download.\n\n",
			$this->locale->formatNumber(count($media)),
			$this->locale->formatNumber(count($originals_to_download))));

		$this->downloadOriginals($originals_to_download);
	}

	// }}}
	// {{{ protected function mediaFileOriginalIsDownloadable()

	protected function mediaFileOriginalIsDownloadable(array $media_file)
	{
		$downloadable = false;

		// don't download originals for videos that are marked to be downloaded,
		// and only download the originals for those marked with the original
		// missing.
		if ((strpos($media_file['tags'], $this->delete_tag) === false) &&
			(strpos($media_file['tags'], $this->original_missing_tag)
				!= false)) {
			$downloadable = true;
		}

		return $downloadable;
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
				// we have to use the passthrough because you can't download
				// the original.
				$passthrough = $this->toaster->getPassthroughByKey($key);
				$original    = $this->toaster->getOriginalByKey($key);
				$source      = $this->toaster->getMediaDownloadByKeys($key,
					$passthrough['template']['key']);
var_dump($source);

var_dump($key);
var_dump($original['key']);
var_dump($passthrough['key']);
$this->toaster->deleteEncodingByKey($passthrough['key']);
$this->toaster->deleteEncodingByKey($original['key']);
exit;
//PDc7v5sO
				$destination = sprintf('%s/%s',
					$this->getDestinationDirectory($key),
					$media_file['custom']['original_filename']);
var_dump($source); var_dump($destination);
exit;
				$this->download($source, $destination, $key, $filesize);

				// delete passthrough and originals. the remove the
				// download_pending tag
				//$this->list->deleteEncodingByKey();
				//$this->list->deleteEncodingByKey();
				//$this->list->updateMediaRemoveTagsByKey($key,
				//	array($this->original_missing_tag));
			}



			$this->debug(sprintf("%s - Media: %s (%s) ... \n",
				$this->locale->formatNumber($count++),
				$media_file['key'],
				SwatString::byteFormat($binding->filesize)));

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
