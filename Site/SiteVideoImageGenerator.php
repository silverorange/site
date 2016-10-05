<?php

require_once 'Site/Site.php';
require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteCommandLineArgument.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/dataobjects/SiteVideoMedia.php';
require_once 'FFmpegPHP2/FFmpegAutoloader.php';

/**
 * Generates media thumbnails for the video scrubber
 *
 * @package   Site
 * @copyright 2013-2016 silverorange
 */
abstract class SiteVideoImageGenerator extends
	SiteCommandLineApplication
{
	// {{{ protected properties

	/**
	 * The directory containing the media hierarchy
	 *
	 * @var string
	 */
	protected $media_file_base;

	/**
	 * The directory containing the image hierarchy
	 *
	 * @var string
	 */
	protected $image_file_base;

	// }}}
	// {{{ public function setMediaFileBase()

	public function setMediaFileBase($media_file_base)
	{
		$this->media_file_base = $media_file_base;
	}

	// }}}
	// {{{ public function setImageFileBase()

	public function setImageFileBase($image_file_base)
	{
		$this->image_file_base = $image_file_base;
	}

	// }}}
	// {{{ public function run()

	public function run()
	{
		parent::run();

		if ($this->image_file_base == '') {
			throw new SiteCommandLineException('Image file base must be set');
		} elseif ($this->media_file_base == '') {
			throw new SiteCommandLineException('Media file base must be set');
		}
	}

	// }}}
	// {{{ protected function getMediaPath()

	protected function getMediaPath(SiteMedia $media, $encoding_shortname)
	{
		$path = null;

		if ($media->encodingExists($encoding_shortname)) {
			$path = $media->getFilePath($encoding_shortname);

			if (!file_exists($path)) {
				$message= "'".$path."' not found for media ".$media->id;
				$exception = new SiteCommandLineException($message);
				$exception->processAndContinue();
				$this->debug($message."\n\n");
				$path = null;
			}
		} else {
			$message = sprintf(
				"Encoding '%s' not found for media: %s",
				$encoding_shortname,
				$media->id
			);

			$exception = new SiteCommandLineException($message);
			$exception->processAndContinue();
			$this->debug($message."\n\n");
		}

		return $path;
	}

	// }}}

	// boilerplate
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this search indexer
	 *
	 * @return array the list of modules to load for this application.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'SiteConfigModule',
			'database' => 'SiteDatabaseModule',
		);
	}

	// }}}
	// {{{ protected function configure()

	protected function configure(SiteConfigModule $config)
	{
		parent::configure($config);
		$this->database->dsn = $config->database->dsn;
	}

	// }}}
}

?>
