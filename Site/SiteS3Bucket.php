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
	// {{{ constants

	/**
	 * @var string
	 */
	const DEFAULT_PROTOCOL = 's3';

	// }}}
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $bucket;

	/**
	 * @var string
	 */
	protected $access_key_id;

	/**
	 * @var string
	 */
	protected $access_key_secret;

	/**
	 * @var array
	 */
	protected $protocols = array(
		'image/jpeg' => 's3+jpg',
		'image/tiff' => 's3+tif',
		'image/png'  => 's3+png',
	);

	// }}}
	// {{{ public function __construct()

	public function __construct($bucket, $access_key_id, $access_key_secret)
	{
		$this->bucket            = $bucket;
		$this->access_key_id     = $access_key_id;
		$this->access_key_secret = $access_key_secret;

		$this->registerStreams();
	}

	// }}}
	// {{{ public function copyToCDN()

	public function copyToCDN($source, $destination, $mime_type = null)
	{
		$protocol    = $this->getPseudoProtocol($mime_type);
		$destination = sprintf('%s://%s/%s',
			$protocol,
			$this->bucket,
			$destination);

		copy($source, $destination);
	}

	// }}}
	// {{{ public function deleteFromCDN()

	public function deleteFromCDN($file_path, $mime_type = null)
	{
		$protocol  = $this->getPseudoProtocol($mime_type);
		$file_path = sprintf('%s://%s/%s',
			$protocol,
			$this->bucket,
			$file_path);

		unlink($file_path);
	}

	// }}}
	// {{{ protected function registerStreams()

	protected function registerStreams()
	{
		$acl = Services_Amazon_S3_AccessControlList::ACL_PUBLIC_READ;

		$options = array(
			'acl'               => $acl,
			'access_key_id'     => $this->access_key_id,
			'secret_access_key' => $this->access_key_secret,
		);

		// First register a generic S3 stream.
		Services_Amazon_S3_Stream::register(self::DEFAULT_PROTOCOL, $options);

		// Then register S3 streams that set the correct mime_type for the data
		// being transferred.
		foreach ($this->protocols as $mime_type => $protocol) {
			$options['content_type'] = $mime_type;

			Services_Amazon_S3_Stream::register($protocol, $options);
		}
	}

	// }}}
	// {{{ protected function getPseudoProtocol()

	protected function getPseudoProtocol($mime_type)
	{
		$protocol = self::DEFAULT_PROTOCOL;

		if (isset($this->protocols[$mime_type])) {
			$protocol = $this->protocols[$mime_type];
		}

		return $protocol;
	}

	// }}}
}

?>
