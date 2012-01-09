<?php

require_once 'Services/Amazon/S3.php';
require_once 'Services/Amazon/S3/AccessControlList.php';
require_once 'Swat/exceptions/SwatFileNotFoundException.php';
require_once 'Site/SiteCdnModule.php';
require_once 'Site/exceptions/SiteCdnException.php';
//require_once 'Site/exceptions/SiteAmazonCdnFilesizeException.php';

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

	/**
	 * A fileinfo resource for looking up MIME types
	 *
	 * @var magic database resource
	 */
	protected $finfo;

	/**
	 * Whether or not to check the md5 of a file when saving.
	 *
	 * If true, check to see if the object already exists and checks the current
	 * file's md5 against the new file's md5 before copying. Defaults to true.
	 *
	 * md5 is always saved in a custom metadata field. ETag isn't used because
	 * amazon doesn't guarantee it to always be the file's md5, even though it
	 * appears as though it always is.
	 *
	 * @var boolean
	 */
	protected $check_md5 = true;

	/**
	 * Whether or not to update metadata if saving an file that already exists.
	 *
	 * If true, and the object already exists, update the existing metadata on
	 * the object when attempting a copy. If false, do nothing on copy. Defaults
	 * to true.
	 *
	 * @var boolean
	 */
	protected $update_metadata = true;

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
	// {{{ public function setCheckMd5()

	/**
	 * Sets whether or not to check the md5 field;
	 *
	 * @param boolean $check_md5 true if we want to check it, false if we don't.
	 */
	public function setCheckMd5($check_md5 = true)
	{
		$this->check_md5 = (bool) $check_md5;
	}

	// }}}
	// {{{ public function setUpdateMetadata()

	/**
	 * Sets whether or not to update metadata when attempting to copy a file
	 * that already exists on the CDN.
	 *
	 * @param boolean $update_metadata true if we want to update the file, false
	 *                                  if we don't.
	 */
	public function setUpdateMetadata($update_metadata = true)
	{
		$this->update_metadata = (bool) $update_metadata;
	}

	// }}}
	// {{{ public function copyFile()

	/**
	 * Copies a file to the Amazon S3 bucket
	 *
	 * @param string $source the source path of the file to copy.
	 * @param string $destination the destination path of the file to copy.
	 * @param string $mime_type the MIME type of the file. If null, we grab the
	 *                           MIME type from the file. Defaults to null.
	 * @param string $access_type the access type, public/private, of the file.
	 *                              Defaults to 'public'.
	 * @param array $http_headers HTTP headers associated with the file.
	 *                             Defaults to an empty array.
	 * @param array $metadata metadata associated with the file.
	 *                         Defaults to an empty array.
	 *
	 * @returns boolean true if the file was copied, false if a file with the
	 *                   same path and md5 already exists on s3.
	 *
	 * @throws SwatFileNotFoundException if the source file doesn't exist
	 * @throws SiteCdnException if the CDN encounters any problems
	 */
	public function copyFile($source, $destination, $mime_type = null,
		$access_type = 'public', $http_headers = array(), $metadata = array())
	{
		$copied = false;

		try {
			if (file_exists($source) === false) {
				throw new SwatFileNotFoundException(sprintf(
					Site::_('Unable to locate file ‘%s’.'), $source));
			}

			$metadata['md5'] = md5_file($source);

			$s3_object = $this->bucket->getObject($destination);
			$copy      = true;
			$update    = false;

			if ($this->check_md5 &&
				$s3_object->load(
					Services_Amazon_S3_Resource_Object::LOAD_METADATA_ONLY) &&
				array_key_exists('md5', $s3_object->userMetadata)) {

				$copy = ($s3_object->userMetadata['md5'] !== $metadata['md5']);
				$update = ($copy === false && $this->update_metadata);
			}

			if ($copy) {
				$copied = true;
				if ($mime_type === null) {
					$finfo     = $this->getFinfo();
					$mime_type = finfo_file($finfo, $source);
				}

				$file = file_get_contents($source);
				if ($file === false) {
					throw new SwatFileNotFoundException(sprintf(
						Site::_('Unable to open file ‘%s’.'), $source));
				}

				$s3_object->data         = $file;
				$s3_object->contentType  = $mime_type;
				$s3_object->acl          = $this->getAcl($access_type);
				$s3_object->httpHeaders  = $http_headers;
				$s3_object->userMetadata = $metadata;

				$s3_object->save();
			} elseif ($update) {
				$copied = $this->updateFile($destination, $mime_type,
					$access_type, $http_headers, $metadata);
			}
		} catch (Services_Amazon_S3_Exception $e) {
			throw new SiteCdnException($e);
		}

		return $copied;
	}

	// }}}
	// {{{ public function updateFile()

	/**
	 * Copies a file to the Amazon S3 bucket
	 *
	 * @param string $path the path of the file to update.
	 * @param string $access_type the access type, public/private, of the file.
	 *                              Defaults to 'public'.
	 * @param array $http_headers HTTP headers associated with the file.
	 *                             Defaults to an empty array.
	 * @param array $metadata metadata associated with the file.
	 *                         Defaults to an empty array.
	 *
	 * @returns boolean true if the file was copied, false otherwise.
	 *
	 * @throws SiteCdnException if the CDN encounters any problems
	 */
	public function updateFileMetadata($path, $mime_type = null,
		$access_type = 'public', $http_headers = array(), $metadata = array())
	{
		$updated = false;

		try {
			$s3_object = $this->bucket->getObject($path);

			// load existing metadata so we can re-save what doesn't change
			$s3_object->load(
				Services_Amazon_S3_Resource_Object::LOAD_METADATA_ONLY);

			// preserve mime_type if no new mime_type has been set.
			$s3_object->contentType = ($mime_type === null) ?
				$s3_object->contentType :
				$mime_type;

			// preserve acl if no new acl has been set.
			$s3_object->acl = ($access_type === null) ?
				$s3_object->acl :
				$this->getAcl($access_type);

			// merge existing headers and metadata with new values instead
			// of completely overwriting. This is so we can only update one
			// of the values instead of having to set everything from
			// scratch.
			$s3_object->httpHeaders = array_merge(
				$s3_object->httpHeaders,
				$http_headers);

			$s3_object->userMetadata = array_merge(
				$s3_object->userMetadata,
				$metadata);

			// false as second parameter prevents the metadata being copied
			// wholesale from the original, and allows the file to copy in
			// place with the new metadata.
			$s3_object->copyFrom($s3_object, false);

			$updated = true;
		} catch (Services_Amazon_S3_Exception $e) {
			throw new SiteCdnException($e);
		}

		return $updated;
	}

	// }}}
	// {{{ public function deleteFile()

	/**
	 * Deletes a file from the Amazon S3 bucket
	 *
	 * @param string $file_path the path, in the bucket, of the file to delete.
	 *
	 * @throws SwatFileNotFoundException if the file doesn't exist
	 * @throws SiteCdnException if the CDN encounters any problems
	 */
	public function deleteFile($file_path)
	{
		try {
			if (strlen($file_path) === 0) {
				throw new SwatFileNotFoundException(
					Site::_('Unable to delete file with empty path.'));
			}

			$s3_object = $this->bucket->getObject($file_path);
			$load_type = Services_Amazon_S3_Resource_Object::LOAD_METADATA_ONLY;

			if (!$s3_object->load($load_type)) {
				throw new SwatFileNotFoundException(sprintf(
					Site::_('Unable to locate file ‘%s’ on the CDN.'),
						$file_path));
			}

			$s3_object->delete();
		} catch (Services_Amazon_S3_Exception $e) {
			throw new SiteCdnException($e);
		}
	}

	// }}}
	// {{{ protected function getFinfo()

	protected function getFinfo()
	{
		if ($this->finfo === null) {
			$this->finfo = finfo_open(FILEINFO_MIME);
		}

		return $this->finfo;
	}

	// }}}
	// {{{ protected function getAcl()

	protected function getAcl($access_type)
	{
		switch ($access_type) {
		case 'private':
			$acl = Services_Amazon_S3_AccessControlList::ACL_AUTHENTICATED_READ;
			break;
		default:
			$acl = Services_Amazon_S3_AccessControlList::ACL_PUBLIC_READ;
			break;
		}

		return $acl;
	}

	// }}}
}

?>
