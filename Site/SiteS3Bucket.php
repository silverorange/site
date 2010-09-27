<?php

require_once 'Site/SiteCDN.php';
require_once 'Site/SiteObject.php';
require_once 'Services/Amazon/S3/Stream.php';
require_once 'Services/Amazon/S3/AccessControlList.php';

/**
 * Class that provides access to an amazon S3 bucket.
 *
 * @package   Site
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteS3Bucket extends SiteObject implements SiteCDN
{
	protected $bucket;

	public function __construct($bucket, $access_key_id, $access_key_secret)
	{
		$options = array(
			'access_key_id'     => $access_key_id,
			'secret_access_key' => $access_key_secret,
			'content_type'      => 'image/jpeg',
			'acl' => Services_Amazon_S3_AccessControlList::ACL_PUBLIC_READ,
		);

		$this->bucket = $bucket;

		Services_Amazon_S3_Stream::register('s3', $options);
	}

	public function copyToCDN($source, $destination)
	{
		copy($source, sprintf('s3://%s/%s', $this->bucket, $destination));
	}

	public function deleteFromCDN($file_path)
	{
		unlink(sprintf('s3://%s/%s', $this->bucket, $file_path));
	}
}

?>
