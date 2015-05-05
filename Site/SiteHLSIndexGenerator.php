<?php

require_once 'AWSSDKforPHP/sdk.class.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteCommandLineConfigModule.php';
require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/dataobjects/SiteVideoMediaWrapper.php';
require_once 'Site/exceptions/SiteCommandLineException.php';

/**
 * Application to generate m3u8 index files for HLS video
 *
 * @package   Site
 * @copyright 2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteHLSIndexGenerator extends SiteCommandLineApplication
{
	// {{{ public properties

	/**
	 * S3 SDK
	 *
	 * @var AmazonS3
	 */
	public $s3;

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		parent::__construct($id, $filename, $title, $documentation);

		$this->initModules();
		$this->parseCommandLineArguments();
	}

	// }}}
	// {{{ public function run()

	/**
	 * Runs this application
	 */
	public function run()
	{
		$this->lock();

		foreach ($this->getMedia() as $media) {
			$this->indexMedia($media);
		}

		$this->unlock();
	}

	// }}}
	// {{{ protected function getMedia()

	protected function getMedia()
	{
		return SwatDB::query(
			$this->db,
			sprintf(
				'select * from Media where has_hls = %s order by id',
				$this->db->quote(false, 'boolean')
			),
			SwatDBClassMap::get('SiteVideoMediaWrapper')
		);
	}

	// }}}
	// {{{ protected function indexMedia()

	protected function indexMedia(SiteMedia $media)
	{
		$this->debug("Checking {$media->id}:");

		$index_exists = $this->s3->if_object_exists(
			$this->config->amazon->bucket,
			$this->getHLSPath($media).'/index.m3u8'
		);

		if ($index_exists) {
			$this->debug(' index exists');
			$this->debug("\n");
			$media->has_hls = true;
			$media->save();
		} else {
			$encodings = $this->getEncodingIndexes($media);
			if (count($encodings) === count($media->video_encoding_bindings)) {
				$this->writeIndex($media, $encodings);
				$media->has_hls = true;
				$media->save();

				$this->debug(' index.m3u8 saved');
			} elseif (count($encodings) > 0) {
				$this->debug(' partially encoded');
			} else {
				$this->debug(' no HLS encodings');
			}

			$this->debug("\n");
		}
	}

	// }}}
	// {{{ protected function getHLSPath()

	protected function getHLSPath(SiteMedia $media)
	{
		return sprintf('media/%s/hls', $media->id);
	}

	// }}}
	// {{{ protected function getEncodingIndexes()

	protected function getEncodingIndexes(SiteMedia $media)
	{
		$encodings = array();

		$files = $this->s3->get_object_list(
			$this->config->amazon->bucket,
			array(
				'prefix' => $this->getHLSPath($media)
			)
		);

		foreach ($files as $file) {
			$local_path = substr($file, strlen($this->getHLSPath($media)) + 1);
			$info = pathinfo($local_path);
			if (isset($info['extension']) &&
				$info['extension'] == 'm3u8' &&
				$info['dirname'] != '.') {

				$path_parts = explode('/', $info['dirname']);
				$shortname = $path_parts[0];
				$binding = $media->getEncodingBinding($shortname);
				$bandwidth = (int)($binding->filesize / $media->duration * 8);
				$encodings[$shortname] = array(
					'path'       => $local_path,
					'resolution' => $binding->width.'x'.$binding->height,
					'bandwidth'  => $bandwidth,
				);
			}
		}

		return $encodings;
	}

	// }}}
	// {{{ protected function writeIndex()

	protected function writeIndex(SiteMedia $media, array $encodings)
	{
		// sort encodings by highest-to-lowest
		krsort($encodings, SORT_NUMERIC);

		$file_contents = "#EXTM3U\n";
		foreach ($encodings as $encoding) {
			$file_contents.= sprintf(
				"#EXT-X-STREAM-INF:PROGRAM-ID=1, BANDWIDTH=%s, RESOLUTION=%s\n%s\n",
				$encoding['bandwidth'],
				$encoding['resolution'],
				$encoding['path']
			);
		}

		$acl = ($media->private)
			? AmazonS3::ACL_AUTH_READ
			: AmazonS3::ACL_PUBLIC;

		$this->s3->create_object(
			$this->config->amazon->bucket,
			$this->getHLSPath($media).'/index.m3u8',
			array(
				'acl' => $acl,
				'body' => $file_contents,
				'contentType' => 'application/x-mpegURL',
			)
		);
	}

	// }}}

	// boilerplate code
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'database' => 'SiteDatabaseModule',
		);
	}

	// }}}
	// {{{ protected function configure()

	protected function configure(SiteConfigModule $config)
	{
		parent::configure($config);

		$this->database->dsn = $config->database->dsn;

		$this->s3 = new AmazonS3(
			array(
				'key'    => $config->amazon->access_key_id,
				'secret' => $config->amazon->access_key_secret,
			)
		);
	}

	// }}}
}

?>
