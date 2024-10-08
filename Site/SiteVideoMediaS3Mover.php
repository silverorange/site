<?php

use Aws\S3\S3Client;

/**
 * Application to copy video to the new S3 directory structure.
 *
 * Temporary script until we can fix our encoding process to include HLS.
 *
 * @copyright 2015-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SiteVideoMediaMover
 */
class SiteVideoMediaS3Mover extends SiteVideoMediaMover
{
    /**
     * S3 SDK.
     *
     * @var S3Client
     */
    protected $s3;

    public function getBucket()
    {
        return $this->config->amazon->bucket;
    }

    protected function getOldPath(SiteVideoMedia $media, $shortname)
    {
        return sprintf(
            'media/%s/%s/%s',
            $media->media_set->shortname,
            $shortname,
            $this->getOldFilename($media, $shortname)
        );
    }

    protected function getNewPath(SiteVideoMedia $media, $shortname)
    {
        return sprintf(
            'media/%s/full/%s',
            $media->id,
            $this->getNewFilename($media, $shortname)
        );
    }

    protected function hasFile($path)
    {
        return $this->s3->doesObjectExist($this->getBucket(), $path);
    }

    protected function moveFile(SiteVideoMedia $media, $old_path, $new_path)
    {
        $acl = ($media->media_set->private)
            ? 'authenticated-read'
            : 'public-read';

        $copy_source = sprintf(
            '%s/%s',
            $this->getBucket(),
            S3Client::encodeKey($old_path)
        );

        $this->s3->copyObject(
            ['ACL' => $acl, 'Bucket' => $this->getBucket(), 'CopySource' => $copy_source, 'Key' => $new_path]
        );
    }

    protected function cleanUp($path)
    {
        $this->s3->deleteObject(
            ['Bucket' => $this->getBucket(), 'Key' => $path]
        );
    }

    // boilerplate code

    protected function configure(SiteConfigModule $config)
    {
        parent::configure($config);

        $this->s3 = new S3Client(
            ['version' => 'latest', 'region' => $config->amazon->region, 'credentials' => ['key' => $config->amazon->access_key_id, 'secret' => $config->amazon->access_key_secret]]
        );
    }
}
