<?php

require_once 'Site/SiteCdnModule.php';
require_once 'Site/exceptions/SiteCdnException.php';

/**
 * Application module that provides access to an Amazon S3 bucket.
 *
 * @package   Site
 * @copyright 2010-2016 silverorange
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
	 * Whether or not cloudfront should be used for CDN resources.
	 *
	 * @var boolean
	 */
	public $cloudfront_enabled;

	/**
	 * CloudFront streaming distribution
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
	 * @var Aws\S3\S3Client
	 */
	protected $s3;

	/**
	 * The Amazon CloudFront accessor
	 *
	 * @var Aws\CloudFront\CloudFrontClient
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

		$sdk = new Aws\Sdk(
			array(
				'version' => 'latest',
				'region'  => $this->app->config->amazon->region,
				'credentials' => array(
					'key'    => $this->access_key_id,
					'secret' => $this->access_key_secret,
				),
			)
		);

		$this->s3 = $sdk->createS3();

		if ($this->cloudfront_enabled) {
			$this->cf = $sdk->createCloudFront();
		}
	}

	// }}}
	// {{{ public function setDistributionPrivateKey()

	public function setDistributionPrivateKey($distribution_private_key_file,
		$distribution_key_pair_id = null)
	{
		if (file_exists($distribution_private_key_file)) {
			$this->distribution_private_key = file_get_contents(
				$distribution_private_key_file
			);
		} else {
			throw new SiteCdnException(
				sprintf(
					'Distribution Private Key ‘%s’ missing.',
					$file
				)
			);
		}

		if ($distribution_key_pair_id !== null) {
			$this->distribution_key_pair_id = $distribution_key_pair_id;
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
	public function copyFile($filename, $source, $headers,
		$access_type = 'private')
	{
		if (!is_file($source)) {
			throw new SiteCdnException(
				sprintf(
					'“%s” is not a regular file.',
					$source
				)
			);
		}

		if (!is_readable($source)) {
			throw new SiteCdnException(
				sprintf(
					'Unable to read “%s.”',
					$source
				)
			);
		}

		// Get ACL value for S3
		$acl = 'private';
		if (strcasecmp($access_type, 'public') === 0) {
			$acl = 'public-read';
		}

		// Get MD5 from S3 and from local file.
		$metadata = $this->getMetadata($filename);
		$metadata = $metadata['Metadata'];
		$new_md5 = md5_file($source);
		$old_md5 = (isset($metadata['md5'])) ? $metadata['md5'] : '';

		// Convert HTTP headers into S3 options
		$header_options = array();
		if (is_array($headers)) {
			if (isset($headers['Cache-Control'])) {
				$header_options['CacheControl'] = $headers['Cache-Control'];
			}
			if (isset($headers['Content-Type'])) {
				$header_options['ContentType'] = $headers['Content-Type'];
			}
			if (isset($headers['Content-Disposition'])) {
				$header_options['ContentDisposition'] =
					$headers['Content-Disposition'];
			}
		}

		if ($old_md5 != '' && $new_md5 === $old_md5) {
			// If MD5 from local file matches S3, just update the metadata.
			$copy_source = sprintf(
				'%s/%s',
				$this->bucket,
				Aws\S3\S3Client::encodeKey($filename)
			);
			$options = array_merge(
				array(
					'ACL'               => $acl,
					'Bucket'            => $this->bucket,
					'CopySource'        => $copy_source,
					'Key'               => $filename,
					'Metadata'          => $metadata,
					'MetadataDirective' => 'REPLACE',
					'StorageClass'      => $this->storage_class,
				),
				$header_options
			);
			try {
				$this->s3->copyObject($options);
			} catch (Aws\Exception\AwsException $e) {
				throw new SiteCdnException($e);
			}
		} else {
			// If the MD5 on S3 does not match, upload the file content and
			// set the MD5 in the S3 object metadata.
			$metadata['md5'] = $new_md5;
			$options = array_merge(
				array(
					'ACL'          => $acl,
					'Bucket'       => $this->bucket,
					'Key'          => $filename,
					'Metadata'     => $metadata,
					'SourceFile'   => $source,
					'StorageClass' => $this->storage_class,
				),
				$header_options
			);
			try {
				$this->s3->putObject($options);
			} catch (Aws\Exception\AwsException $e) {
				throw new SiteCdnException($e);
			}
		}
	}

	// }}}
	// {{{ public function moveFile()

	/**
	 * Moves a file around in the S3 bucket.
	 *
	 * @param string $old_filename the current name of the file to move.
	 * @param string $new_filename the new name of the file to move.
	 * @param string $access_type  the access type, public/private, of the file.
	 *
	 * @throws SiteCdnException if the CDN encounters any problems
	 */
	public function moveFile($old_filename, $new_filename,
		$access_type = 'private')
	{
		// The getObjectAcl method returns extremely detailed information about
		// user access levels. We just want to set 'private' or 'public-read'
		// for the moved file. Since we can't easily look up the old ACL, at
		// least support passing in a new ACL
		$acl = 'private';
		if (strcasecmp($access_type, 'public') === 0) {
			$acl = 'public-read';
		}

		$copy_source = sprintf(
			'%s/%s',
			$this->bucket,
			Aws\S3\S3Client::encodeKey($old_filename)
		);

		try {
			$this->s3->copyObject(
				array(
					'ACL'          => $acl,
					'Bucket'       => $this->bucket,
					'CopySource'   => $copy_source,
					'Key'          => $new_filename,
					'StorageClass' => $this->storage_class,
				)
			);
		} catch (Aws\Exception\AwsException $e) {
			throw new SiteCdnException($e);
		}

		// S3 has no concept of move, so remove the old version once it has
		// been copied.
		$this->removeFile($old_filename);
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
		try {
			$this->s3->deleteObject(
				array(
					'Bucket' => $this->bucket,
					'Key'    => $filename,
				)
			);
		} catch (Aws\Exception\AwsException $e) {
			throw new SiteCdnException($e);
		}
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
	 * @param boolean $secure whether or not to use HTTPS. If not set, the
	 *                        value will fall back to
	 *                        SiteWebApplication::isSecure().
	 */
	public function getUri($filename, $expires = null, $secure = null)
	{
		$uri = null;

		if ($secure === null) {
			$secure = $this->app->isSecure();
		}

		if ($this->app->config->amazon->cloudfront_enabled &&
			$this->cf instanceof Aws\CloudFront\CloudFrontClient) {
			$uri = $this->getCloudFrontUri($filename, $expires, false, $secure);
		} else {
			if ($expires === null) {
				$uri = $this->s3->getObjectUrl(
					array(
						'Bucket' => $this->bucket,
						'Key'    => $filename
					)
				);
			} else {
				$command = $this->s3->getCommand(
					'GetObject',
					array(
						'Bucket' => $this->bucket,
						'Key'    => $filename,
					)
				);

				$request = $this->s3->createPresignedRequest(
					$command,
					$expires
				);

				$uri = $request->getUri();
			}

			if (!$secure) {
				$uri = preg_replace('/^https:/', 'http:', $uri);
			}
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
	 * @param boolean $secure whether or not to use HTTPS. If not set, the
	 *                        value will fall back to
	 *                        SiteWebApplication::isSecure().
	 */
	public function getStreamingUri($filename, $expires = null, $secure = null)
	{
		return $this->getCloudFrontUri($filename, $expires, true, $secure);
	}

	// }}}
	// {{{ public function getMetadata()

	public function getMetadata($filename)
	{
		return $this->s3->headObject(
			array(
				'Bucket' => $this->bucket,
				'Key'    => $filename
			)
		);
	}

	// }}}
	// {{{ public function hasStreamingDistribution()

	public function hasStreamingDistribution()
	{
		return (
			$this->cf instanceof Aws\CloudFront\CloudFrontClient &&
			$this->streaming_distribution !== null
		);
	}

	// }}}
	// {{{ protected function getCloudFrontUri()

	protected function getCloudFrontUri($filename, $expires = null,
		$streaming = null, $secure = null)
	{
		$config = $this->app->config->amazon;

		if (!$config->cloudfront_enabled ||
			!$this->cf instanceof Aws\CloudFront\CloudFrontClient) {
			throw new SwatException(
				'CloudFront must be enabled to get CloudFront URIs in the '.
				'Amazon CDN module'
			);
		}

		if ($streaming) {
			if (!$this->hasStreamingDistribution()) {
				throw new SwatException(
					'Streaming distribution must be specified for streaming '.
					'URIs in the Amazon CDN module'
				);
			}

			$distribution = ($expires === null)
				? 'streaming_distribution'
				: 'private_streaming_distribution';

		} else {
			$distribution = ($expires === null)
				? 'distribution'
				: 'private_distribution';
		}

		if ($config->$distribution === null) {
			throw new SwatException(
				sprintf(
					'amazon.%s config setting must be set.',
					$distribution
				)
			);
		}

		if ($secure === null) {
			 $secure = $this->app->isSecure();
		}

		$uri = sprintf(
			'%s://%s/%s',
			$secure ? 'https' : 'http',
			$config->$distribution,
			$filename
		);

		if ($expires !== null) {
			$uri = $this->cf->getSignedUrl(
				array(
					'url'         => $uri,
					'expires'     => $expires,
					'key_pair_id' => $this->distribution_key_pair_id,
					'private_key' => $this->distribution_private_key,
				)
			);
		}

		return $uri;
	}

	// }}}
}

?>
