<?php

require_once 'AWSSDKforPHP/sdk.class.php';
require_once 'Site/SiteCdnModule.php';
require_once 'Site/exceptions/SiteCdnException.php';

/**
 * Application module that provides access to an Amazon S3 bucket.
 *
 * @package   Site
 * @copyright 2010-2013 silverorange
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

	/**
	 * Cloudfront streaming distribution
	 *
	 * @var string
	 */
	public $streaming_distribution;

	/**
	 * CloudFront distribution key-pair id
	 *
	 * @var string
	 */
	public $distribution_key_pair_id;

	/**
	 * Filename of the file containing the CloudFront distribution private key
	 *
	 * @var string
	 */
	public $distribution_private_key_file;

	// }}}
	// {{{ protected properties

	/**
	 * The Amazon S3 accessor
	 *
	 * @var AmazonS3
	 */
	protected $s3;

	/**
	 * The Amazon CloudFront accessor
	 *
	 * @var AmazonCloudFront
	 */
	protected $cf;

	/**
	 * Storage class to use for storing the object.
	 *
	 * Must be one of STANDARD (99.999999999%, two facilities) or
	 * REDUCED_REDUNDANCY (99.99%, one facility).
	 *
	 * @var string
	 */
	protected $storage_class = 'STANDARD';

	/**
	 * CloudFront distribution private key
	 *
	 * @var string
	 */
	protected $distribution_private_key;

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this module
	 */
	public function init()
	{
		if ($this->access_key_id === null ||
			$this->access_key_secret === null) {

			throw new SiteCdnException(
				'Access keys are required for the Amazon CDN module'
			);
		}

		$this->s3 = new AmazonS3(
			array(
				'key' => $this->access_key_id,
				'secret' => $this->access_key_secret,
			)
		);

		if ($this->distribution_key_pair_id !== null &&
			$this->distribution_private_key_file !== null) {
			$this->cf = new AmazonCloudFront(
				array(
					'key'    => $this->app->config->amazon->access_key_id,
					'secret' => $this->app->config->amazon->access_key_secret,
				)
			);

			$this->cf->set_keypair_id($this->distribution_key_pair_id);

			// TODO: This assumes a file location. We should do this better.
			$file = sprintf(
				'../system/amazon/%s',
				$this->distribution_private_key_file
			);

			if (file_exists($file)) {
				$this->distribution_private_key = file_get_contents($file);
			} else {
				throw new SiteCdnException(
					sprintf(
						'Distribution Private Key ‘%s’ missing.',
						$file
					)
				);
			}

			$this->cf->set_private_key($this->distribution_private_key);
		}
	}

	// }}}
	// {{{ public function setStandardRedundancy()

	public function setStandardRedundancy()
	{
		$this->storage_class = 'STANDARD';
	}

	// }}}
	// {{{ public function setReducedRedundancy()

	public function setReducedRedundancy()
	{
		$this->storage_class = 'REDUCED_REDUNDANCY';
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
		$headers['x-amz-storage-class'] = $this->storage_class;

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
	// {{{ public function getUri()

	/**
	 * Gets a URI for a file on the CDN
	 *
	 * @param string $filename the name of the file.
	 * @param string $expires expiration time expressed either as a number
	 *                        of seconds since UNIX Epoch, or any string
	 *                        that strtotime() can understand
	 */
	public function getUri($filename, $expires = null)
	{
		$uri = '';

		if ($expires === null) {
			$uri = sprintf(
				'%s%s',
				$this->app->isSecure() ?
					$this->app->config->uri->secure_cdn_base :
					$this->app->config->uri->cdn_base,
				rawurlencode($filename)
			);
		} else {
			$options = array(
				'https' => $this->app->isSecure(),
			);

			// TODO: replace this with commented out block below so we can use
			// cloudfront where appropriate.
			$uri = $this->s3->get_object_url(
				$this->bucket,
				$filename,
				$expires,
				$options
			);

			// TODO: add enabled flag for cloudfront. get private_object_url
			// expects the base to not include trailing slash and protocal, but
			// cdn base does.
			/*
			if ($this->cf instanceof AmazonCloudFront) {
				$uri = $this->cf->get_private_object_url(
					$this->app->isSecure() ?
						$this->app->config->uri->secure_cdn_base :
						$this->app->config->uri->cdn_base,
					$filename,
					$expires,
					$options
				);
			} else {
				$uri = $this->s3->get_object_url(
					$this->bucket,
					$filename,
					$expires,
					$options
				);
			}
			*/
		}

		return $uri;
	}

	// }}}
	// {{{ public function getStreamingUri()

	/**
	 * Gets a streaming URI for a file on the CDN
	 *
	 * @param string $filename the name of the file.
	 * @param string $expires expiration time expressed either as a number
	 *                        of seconds since UNIX Epoch, or any string
	 *                        that strtotime() can understand
	 */
	public function getStreamingUri($filename, $expires = null)
	{
		if (!$this->hasStreamingDistribution()) {
			throw new SwatException('Distribution keys are required for '.
				'streaming URIs in the Amazon CDN module');
		}

		return $this->cf->get_private_object_url(
			$this->streaming_distribution,
			$filename,
			$expires
		);
	}

	// }}}
	// {{{ public function getMetadata()

	public function getMetadata($filename)
	{
		return $this->s3->get_object_metadata($this->bucket, $filename);
	}

	// }}}
	// {{{ public function hasStreamingDistribution()

	public function hasStreamingDistribution()
	{
		return (
			$this->cf instanceof AmazonCloudFront &&
			$this->streaming_distribution !== null
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
