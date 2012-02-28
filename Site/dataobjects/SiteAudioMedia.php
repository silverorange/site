<?php

require_once 'Site/dataobjects/SiteMedia.php';

/**
 * An audio specific media object
 *
 * @package   Site
 * @copyright 2011 silverorange
 */
class SiteAudioMedia extends SiteMedia
{
	// {{{ public function getFormattedDuration()

	/**
	 * Returns the duration of the media in a human readable format.
	 *
	 * DateIntervals were dismissed because creating 10-20 SwatDate's per page
	 * seemed slow and excessive. This is a slightly simplified version of
	 * SwatString::toHumanReadableTimePeriod() to allow for custom formatting.
	 * It always returns the two largest time parts.
	 *
	 * @returns string
	 */
	public function getFormattedDuration()
	{
		// don't care about micro-seconds.
		$seconds = floor($this->duration);
		$minutes = 0;

		if ($seconds > 60) {
			$minutes = floor($seconds / 60);
			$seconds -= 60 * $minutes;
		}

		// use sprintf for padding, because I read somewhere on the internet it
		// was faster than str_pad.
		return sprintf('%1$s:%2$02s min',
			$minutes,
			$seconds);
	}

	// }}}

	// Processing methods
	// {{{ public function process()

	public function process($file_path)
	{
		$this->checkDB();

		try {
			$transaction = new SwatDBTransaction($this->db);

			$this->duration = SiteAudioMedia::parseDuration($file_path);
			$this->filename = ($this->getMediaSet()->obfuscate_filename) ?
				sha1(uniqid(rand(), true)) : null;

			$this->createdate = new SwatDate();
			$this->createdate->toUTC();

			$this->save();

			foreach ($this->getMediaSet()->encodings as $encoding) {
				$this->processEncoding($file_path, $encoding);
			}

			$transaction->commit();
		// TODO: Specialize this.
		} catch (Exception $e) {
			throw $e;
			$transaction->rollback();
		}
	}

	// }}}
	// {{{ protected function processEncoding()

	protected function processEncoding($file_path, SiteMediaEncoding $encoding)
	{
		$binding = new SiteMediaEncodingBinding();
		$binding->setDatabase($this->db);
		$binding->media = $this->id;
		$binding->media_encoding = $encoding->id;
		$binding->media_type = $encoding->getInternalValue('default_type');
		$binding->filesize = filesize($file_path);
		$binding->save();

		if ($this->getMediaSet()->use_cdn) {
			$this->queueCdnTask('copy', $encoding);
		}

		$directory = $this->getFileDirectory($encoding->shortname);
		if (!file_exists($directory) && !mkdir($directory, 0777, true)) {
			throw new SiteException('Unable to create media directory.');
		}

		$filename = $this->getFilePath($encoding->shortname);
		if (!copy($file_path, $filename)) {
			throw new SiteException('Unable to copy media file.');
		}

		$this->encoding_bindings->add($binding);
	}

	// }}}

	// File and URI methods
	// {{{ public function getContentDispositionFilename()

	public function getContentDispositionFilename($encoding_shortname)
	{
		// Convert to an ASCII string. Approximate non ACSII characters.
		$filename = iconv('UTF-8', 'ASCII//TRANSLIT', $this->original_filename);

		// Format the filename according to the qtext syntax in RFC 822
		$filename = str_replace(array("\\", "\r", "\""),
			array("\\\\", "\\\r", "\\\""), $filename);

		return $filename;
	}

	// }}}

	// static convenience methods
	// {{{ public static function parseDuration()

	public static function parseDuration($file_path)
	{
		$command = sprintf('ffprobe \'%s\' 2>&1 | grep Duration',
			escapeshellcmd($file_path));

		$line = shell_exec($command);

		$line_parts = explode(' ', trim($line));
		$time_parts = explode(':', substr($line_parts[1], 0, -1));

		return
			(intval($time_parts[0]) * 3600) +
			(intval($time_parts[1]) * 60)   +
			(intval(round($time_parts[2])));
	}

	// }}}
}

?>
