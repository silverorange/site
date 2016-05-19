<?php

require_once 'AWSSDKforPHP/sdk.class.php';
require_once 'Site/SiteVideoMediaMover.php';

/**
 * Application to copy video to the new S3 directory structure
 *
 * Temporary script until we can fix our encoding process to include HLS.
 *
 * @package   Site
 * @copyright 2015-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteVideoMediaMover
 */
class SiteVideoMediaS3Mover extends SiteVideoMediaMover
{
	// {{{ public properties

	/**
	 * S3 SDK
	 *
	 * @var AmazonS3
	 */
	public $s3;

	// }}}
	// {{{ public function getBucket()

	public function getBucket()
	{
		return $this->config->amazon->bucket;
	}

	// }}}
	// {{{ protected function hasOldPath()

	protected function getOldPath(SiteVideoMedia $media, $shortname)
	{
		return sprintf(
			'media/%s/%s/%s',
			$media->media_set->shortname,
			$shortname,
			$this->getOldFilename($media, $shortname)
		);
	}

	// }}}
	// {{{ protected function hasNewPath()

	protected function getNewPath(SiteVideoMedia $media, $shortname)
	{
		return sprintf(
			'media/%s/full/%s',
			$media->id,
			$this->getNewFilename($media, $shortname)
		);
	}

	// }}}
	// {{{ protected function hasFile()

	protected function hasFile($path)
	{
		return $this->s3->if_object_exists($this->getBucket(), $path);
	}

	// }}}
	// {{{ protected function moveFile()

	protected function moveFile(SiteVideoMedia $media, $old_path, $new_path)
	{
		$acl = ($media->private)
			? AmazonS3::ACL_AUTH_READ
			: AmazonS3::ACL_PUBLIC;

		$this->s3->copy_object(
			array(
				'bucket' => $this->getBucket(),
				'filename' => $old_path
			),
			array(
				'bucket' => $this->getBucket(),
				'filename' => $new_path
			),
			array(
				'acl' => $acl,
			)
		);
	}

	// }}}
	// {{{ protected function cleanUp()

	protected function cleanUp($path)
	{
		$this->s3->delete_object($this->getBucket(), $path);
	}

	// }}}

	// boilerplate code
	// {{{ protected function configure()

	protected function configure(SiteConfigModule $config)
	{
		parent::configure($config);

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
