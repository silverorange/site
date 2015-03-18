<?php

require_once 'Site/dataobjects/SiteMedia.php';

/**
 * An audio specific media object
 *
 * @package   Site
 * @copyright 2011-2015 silverorange
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

	public function process(SiteApplication $app, $file_path)
	{
		$this->checkDB();

		try {
			$transaction = new SwatDBTransaction($this->db);

			$this->duration = $this->parseDuration($app, $file_path);
			$this->filename = ($this->getMediaSet()->obfuscate_filename)
				? sha1(uniqid(rand(), true))
				: null;

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
	// {{{ public function parseDuration()

	public function parseDuration(SiteApplication $app, $file_path)
	{
		$duration = null;

		if ($app->hasModule('SiteAMQPModule')) {
			$amqp = $app->getModule('SiteAMQPModule');
			$message = array('filename' => $file_path);
			try {
				$result = $amqp->doSync(
					'media-duration',
					json_encode($message)
				);

				$result = json_decode($result, true);
				$duration = intval($result['duration']);
			} catch (SiteAMQPJobFailureException $e) {
				// Ignore job failure; will just use the old non-amqp code
				// path.
			}
		}

		if ($duration === null) {
			// No AMQP or AMQP failed, just run the duration script on this
			// server.
			$command = sprintf(
				'ffprobe '.
					'-select_streams a '.
					'-show_packets '.
					'-show_entries packet=pts_time '.
					'-v quiet '.
					'%s '.
				'| '.
				'grep pts_time '.
				'| '.
				'tail -1 '.
				'| '.
				'cut -d "=" -f 2 ',
				escapeshellcmd($file_path)
			);

			$duration = intval(
				round(
					trim(
						shell_exec($command)
					)
				)
			);
		}

		return $duration;
	}

	// }}}
	// {{{ protected function processEncoding()

	protected function processEncoding(SiteApplication $app, $file_path,
		SiteMediaEncoding $encoding)
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
}

?>
