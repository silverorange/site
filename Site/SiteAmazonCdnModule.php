<?php

/**
 * Application module that provides access to an Amazon S3 bucket.
 *
 * @package   Site
 * @copyright 2010-2018 silverorange
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
			['version' => 'latest', 'region'  => $this->app->config->amazon->region, 'credentials' => ['key'    => $this->access_key_id, 'secret' => $this->access_key_secret]]
		);

		$this->s3 = $sdk->createS3();

		if ($this->cloudfront_enabled) {
			$this->cf = $sdk->createCloudFront();
		}
	}

	// }}}
	// {{{ public function setDistributionPrivateKey()

	public function setDistributionPrivateKey(
		$distribution_private_key_file,
		$distribution_key_pair_id = null
	) {
		if (file_exists($distribution_private_key_file)) {
			$this->distribution_private_key_file =
				$distribution_private_key_file;
		} else {
			throw new SiteCdnException(
				sprintf(
					'Distribution Private Key ‘%s’ missing.',
					$distribution_private_key_file
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
	public function copyFile(
		$filename,
		$source,
		$headers,
		$access_type = 'private'
	) {
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
		$new_md5 = md5_file($source);
		$old_md5 = (isset($metadata['md5'])) ? $metadata['md5'] : '';

		// Convert HTTP headers into S3 options
		$header_options = [];
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
				['ACL'               => $acl, 'Bucket'            => $this->bucket, 'CopySource'        => $copy_source, 'Key'               => $filename, 'Metadata'          => $metadata, 'MetadataDirective' => 'REPLACE', 'StorageClass'      => $this->storage_class],
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
				['ACL'          => $acl, 'Bucket'       => $this->bucket, 'Key'          => $filename, 'Metadata'     => $metadata, 'SourceFile'   => $source, 'StorageClass' => $this->storage_class],
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
	public function moveFile(
		$old_filename,
		$new_filename,
		$access_type = 'private'
	) {
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
				['ACL'          => $acl, 'Bucket'       => $this->bucket, 'CopySource'   => $copy_source, 'Key'          => $new_filename, 'StorageClass' => $this->storage_class]
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
			$this->s3->deleteMatchingObjects($this->bucket, $filename);
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
	 */
	public function getUri($filename, $expires = null)
	{
		$uri = null;

		if ($this->app->config->amazon->cloudfront_enabled &&
			$this->cf instanceof Aws\CloudFront\CloudFrontClient) {
			$uri = $this->getCloudFrontUri($filename, $expires, false);
		} else {
			if ($expires === null) {
				$uri = $this->s3->getObjectUrl($this->bucket, $filename);
			} else {
				$command = $this->s3->getCommand(
					'GetObject',
					['Bucket' => $this->bucket, 'Key'    => $filename]
				);

				$request = $this->s3->createPresignedRequest(
					$command,
					$expires
				);

				$uri = $request->getUri();
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
	 */
	public function getStreamingUri($filename, $expires = null)
	{
		return $this->getCloudFrontUri($filename, $expires, true);
	}

	// }}}
	// {{{ public function getMetadata()

	public function getMetadata($filename)
	{
		$metadata = [];

		try {
			$result = $this->s3->headObject(
				['Bucket' => $this->bucket, 'Key'    => $filename]
			);

			$metadata = $result['Metadata'];
		} catch (Aws\S3\Exception\S3Exception $e) {
			if ($e->getAwsErrorCode() !== 'NotFound') {
				throw new SiteCdnException($e);
			}
		}

		return $metadata;
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

	protected function getCloudFrontUri(
		$filename,
		$expires = null,
		$streaming = null
	) {
		// 1.x SDK allowed passing strtotime formatted strings for expiration
		// dates. Modern SDK requires an integer.
		if ($expires !== null && is_string($expires)) {
			$expires = strtotime($expires);
		}

		$config = $this->app->config->amazon;

		if (!$config->cloudfront_enabled ||
			!$this->cf instanceof Aws\CloudFront\CloudFrontClient
		) {
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

		if ($streaming) {
			$protocol = 'rtmp';
		} else {
			$protocol = 'https';
		}

		$uri = sprintf(
			'%s://%s/%s',
			$protocol,
			$config->$distribution,
			$filename
		);

		if ($expires !== null) {
			$uri = $this->cf->getSignedUrl(
				['url'         => $uri, 'expires'     => $expires, 'key_pair_id' => $this->distribution_key_pair_id, 'private_key' => $this->distribution_private_key_file]
			);
		}

		return $uri;
	}

	// }}}
}

?>
