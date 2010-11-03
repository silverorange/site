<?php

require_once 'Services/Amazon/S3.php';
require_once 'Services/Amazon/S3/AccessControlList.php';
require_once 'Swat/exceptions/SwatFileNotFoundException.php';
require_once 'Site/SiteCdn.php';
require_once 'Site/SiteApplicationModule.php';

/**
 * Application module that provides access to an Amazon S3 bucket.
 *
 * @package   Site
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAmazonCdnModule extends SiteApplicationModule implements SiteCdn
{
	// {{{ public properties

	/**
	 * The name of the S3 bucket to use
	 *
	 * @var string
	 */
	public $bucket_id;

	/**
	 * The key to use for accessing the bucket
	 *
	 * @var string
	 */
	public $access_key_id;

	/**
	 * The secret to use for accessing the bucket
	 *
	 * @var string
	 */
	public $access_key_secret;

	// }}}
	// {{{ protected properties

	/**
	 * The Amazon S3 bucket
	 *
	 * @var Services_Amazon_S3_Resource_Bucket
	 */
	protected $bucket;

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this module
	 */
	public function init()
	{
		$s3 = Services_Amazon_S3::getAccount(
			$this->access_key_id,
			$this->access_key_secret);

		$this->bucket = $s3->getBucket($this->bucket_id);
	}

	// }}}
	// {{{ public function copyFile()

	/**
	 * Copies a file to the Amazon S3 bucket
	 *
	 * @param string $source the source path of the file to copy.
	 * @param string $destination the destination path of the file to copy.
	 * @param string $mime_type the MIME type of the file. Defaults to null.
	 */
	public function copyFile($source, $destination, $mime_type = null)
	{
		$s3_object = $this->bucket->getObject($destination);
		$s3_object->data = file_get_contents($source);
		$s3_object->acl  =
			Services_Amazon_S3_AccessControlList::ACL_PUBLIC_READ;

		if ($mime_type != '') {
			$s3_object->contentType = $mime_type;
		}

		if ($s3_object->data === false) {
			throw new SwatFileNotFoundException(Site::_(sprintf(
				'Unable to open file ‘%s’.', $source)));
		}

		$s3_object->save();
	}

	// }}}
	// {{{ public function deleteFile()

	/**
	 * Deletes a file from the Amazon S3 bucket
	 *
	 * @param string $file_path the path, in the bucket, of the file to delete.
	 */
	public function deleteFile($file_path)
	{
		$s3_object = $this->bucket->getObject($file_path);
		$s3_object->delete();
	}

	// }}}
}

?>
