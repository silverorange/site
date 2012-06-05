<?php

require_once 'AWSSDKforPHP/sdk.class.php';
require_once 'Site/SiteCdnModule.php';
require_once 'Site/exceptions/SiteCdnException.php';

/**
 * Application module that provides access to an Amazon S3 bucket.
 *
 * @package   Site
 * @copyright 2010-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAmazonCdnModule extends SiteCdnModule
{
	// {{{ public properties

	/**
	 * The name of the S3 bucket to use
	 *
	 * @var string
	 */
	public $bucket;

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
	 * The Amazon S3 accessor
	 *
	 * @var AmazonS3
	 */
	protected $s3;

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this module
	 */
	public function init()
	{
		$this->s3 = new AmazonS3(
			array(
				'key' => $this->access_key_id,
				'secret' => $this->access_key_secret,
			)
		);
	}

	// }}}
	// {{{ public function copyFile()

	/**
	 * Copies a file to the Amazon S3 bucket
	 *
	 * @param string $filename the name of the file to create/replace.
	 * @param string $source the source file to copy to S3.
	 * @param array $headers an array of HTTP headers associated with the file.
	 * @param string $access_type the access type, public/private, of the file.
	 *
	 * @throws SiteCdnException if the CDN encounters any problems
	 */
	public function copyFile($filename, $source, $headers, $access_type)
	{
		if (!is_file($source)) {
			throw new SiteCdnException(
				sprintf(Site::_('“%s” is not a regular file.'), $source)
			);
		}

		if (!is_readable($source)) {
			throw new SiteCdnException(
				sprintf(Site::_('Unable to read “%s.”'), $source)
			);
		}

		$headers['x-amz-meta-md5'] = md5_file($source);

		$acl = AmazonS3::ACL_PRIVATE;

		if (strcasecmp($access_type, 'public') === 0) {
			$acl = AmazonS3::ACL_PUBLIC;
		}

		$metadata = $this->s3->get_object_metadata($this->bucket, $filename);

		$new_md5 = $headers['x-amz-meta-md5'];
		$old_md5 = isset($metadata['Headers']['x-amz-meta-md5']) ?
			$metadata['Headers']['x-amz-meta-md5'] : '';

		if (($old_md5 != '') && ($new_md5 === $old_md5)) {
			$this->handleResponse(
				$this->s3->update_object(
					$this->bucket,
					$filename,
					array(
						'acl' => $acl,
						'headers' => $headers,
					)
				)
			);
		} else {
			$this->handleResponse(
				$this->s3->create_object(
					$this->bucket,
					$filename,
					array(
						'acl' => $acl,
						'headers' => $headers,
						'fileUpload' => $source,
					)
				)
			);
		}
	}

	// }}}
	// {{{ public function removeFile()

	/**
	 * Removes a file from the Amazon S3 bucket
	 *
	 * @param string $filename the name of the file to remove.
	 *
	 * @throws SiteCdnException if the CDN encounters any problems
	 */
	public function removeFile($filename)
	{
		$this->handleResponse(
			$this->s3->delete_object(
				$this->bucket,
				$filename
			)
		);
	}

	// }}}
	// {{{ protected function handleResponse()

	/**
	 * Handles a response from a CDN operation
	 *
	 * @param CFResponse $response the response to the CDN operation.
	 *
	 * @throws SiteCdnException if the response indicates an error
	 */
	protected function handleResponse(CFResponse $response)
	{
		if (!$response->isOK()) {
			if ($response->body instanceof SimpleXMLElement) {
				$message = $response->body->asXML();
			} else {
				$message = 'No error response body provided.';
			}

			throw new SiteCdnException($message, $response->status);
		}
	}

	// }}}
}

?>
