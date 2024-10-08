<?php

/**
 * Application to process queued SiteCdnTasks.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteCdnUpdater extends SiteCommandLineApplication
{
    /**
     * A convenience reference to the database object.
     *
     * @var MDB2_Driver
     */
    public $db;

    /**
     * The directory containing the attachment hierarchy.
     *
     * @var string
     */
    protected $attachment_file_base;

    /**
     * The directory containing the image hierarchy.
     *
     * @var string
     */
    protected $image_file_base;

    /**
     * The directory containing the media hierarchy.
     *
     * @var string
     */
    protected $media_file_base;

    /**
     * An array of the tasks to run.
     *
     * @var array
     */
    protected $tasks = [];

    public function setAttachmentFileBase($attachment_file_base)
    {
        $this->attachment_file_base = $attachment_file_base;
    }

    public function setImageFileBase($image_file_base)
    {
        $this->image_file_base = $image_file_base;
    }

    public function setMediaFileBase($media_file_base)
    {
        $this->media_file_base = $media_file_base;
    }

    /**
     * Runs this application.
     */
    public function run()
    {
        $this->initInternal();
        $this->initTasks();

        $this->lock();
        $this->runInternal();
        $this->unlock();
    }

    // init phase

    /**
     * Initializes this application.
     */
    protected function initInternal()
    {
        $this->initModules();
        $this->parseCommandLineArguments();
    }

    /**
     * Initializes all tasks that we want run.
     */
    protected function initTasks()
    {
        $this->initAttachmentTasks();
        $this->initImageTasks();
        $this->initMediaTasks();
    }

    protected function initAttachmentTasks()
    {
        $sql = sprintf(
            'select * from AttachmentCdnQueue where error_date %s %s',
            SwatDB::equalityOperator(null),
            $this->db->quote(null)
        );

        $tasks = SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(SiteAttachmentCdnTaskWrapper::class)
        );

        // efficiently load attachments
        $attachment_sql = 'select * from Attachment where id in (%s)';
        $attachments = $tasks->loadAllSubDataObjects(
            'attachment',
            $this->db,
            $attachment_sql,
            SwatDBClassMap::get(SiteAttachmentWrapper::class)
        );

        foreach ($tasks as $task) {
            if ($task->attachment instanceof SiteAttachment) {
                $task->attachment->setFileBase($this->attachment_file_base);
            }

            $this->tasks[] = $task;
        }
    }

    protected function initImageTasks()
    {
        $sql = sprintf(
            'select * from ImageCdnQueue where error_date %s %s',
            SwatDB::equalityOperator(null),
            $this->db->quote(null)
        );

        $tasks = SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(SiteImageCdnTaskWrapper::class)
        );

        // efficiently load images
        $image_sql = 'select * from Image where id in (%s)';
        $images = $tasks->loadAllSubDataObjects(
            'image',
            $this->db,
            $image_sql,
            SwatDBClassMap::get(SiteImageWrapper::class)
        );

        // efficiently load dimensions
        $dimension_sql = 'select * from ImageDimension where id in (%s)';
        $dimensions = $tasks->loadAllSubDataObjects(
            'dimension',
            $this->db,
            $dimension_sql,
            SwatDBClassMap::get(SiteImageDimensionWrapper::class)
        );

        foreach ($tasks as $task) {
            if ($task->image instanceof SiteImage) {
                $task->image->setFileBase($this->image_file_base);
            }

            $this->tasks[] = $task;
        }
    }

    protected function initMediaTasks()
    {
        $sql = sprintf(
            'select * from MediaCdnQueue where error_date %s %s',
            SwatDB::equalityOperator(null),
            $this->db->quote(null)
        );

        $tasks = SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(SiteMediaCdnTaskWrapper::class)
        );

        // efficiently load media
        $media_sql = 'select * from Media where id in (%s)';
        $media = $tasks->loadAllSubDataObjects(
            'media',
            $this->db,
            $media_sql,
            SwatDBClassMap::get(SiteMediaWrapper::class)
        );

        // efficiently load encodings
        $encoding_sql = 'select * from MediaEncoding where id in (%s)';
        $encodings = $tasks->loadAllSubDataObjects(
            'encoding',
            $this->db,
            $encoding_sql,
            SwatDBClassMap::get(SiteMediaEncodingWrapper::class)
        );

        foreach ($tasks as $task) {
            if ($task->media instanceof SiteMedia) {
                $task->media->setFileBase($this->media_file_base);
            }

            $this->tasks[] = $task;
        }
    }

    // run phase

    protected function runInternal()
    {
        $message = Site::_('Running %s queued tasks.');
        $this->debug(sprintf($message . "\n", count($this->tasks)), true);

        foreach ($this->tasks as $task) {
            $this->debug($task->getAttemptDescription());

            $task->run($this->cdn);

            $this->debug($task->getResultDescription());
        }

        $this->debug(Site::_('All Done.') . "\n", true);
    }

    // boilerplate code

    protected function configure(SiteConfigModule $config)
    {
        parent::configure($config);

        $this->database->dsn = $config->database->dsn;
    }

    protected function getDefaultModuleList()
    {
        return array_merge(
            parent::getDefaultModuleList(),
            [
                'config'   => SiteConfigModule::class,
                'database' => SiteDatabaseModule::class,
            ]
        );
    }
}
