<?php

/**
 * Download page for attachments.
 *
 * @copyright 2014-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteAttachmentDownload extends AdminPage
{
    protected function getObjectClass()
    {
        return 'SiteAttachment';
    }

    protected function getResolvedObjectClass()
    {
        return SwatDBClassMap::get($this->getObjectClass());
    }

    abstract protected function getFileBase();

    // init phase

    protected function createLayout()
    {
        return new SiteLayout($this->app, SiteFileLoaderLayout::class);
    }

    protected function initInternal()
    {
        parent::initInternal();

        $this->initAttachment();
    }

    protected function initAttachment()
    {
        $id = SiteApplication::initVar(
            'id',
            null,
            SiteApplication::VAR_GET
        );

        if ($id == '') {
            throw new AdminNotFoundException(
                'Attachment id must be specified.'
            );
        }

        $class_name = $this->getResolvedObjectClass();
        $attachment = new $class_name();
        $attachment->setDatabase($this->app->db);

        if (!$attachment instanceof SiteAttachment) {
            throw new AdminNotFoundException(
                'Attachment class must be an instance of SiteAttachment.'
            );
        }

        if ($attachment->load($id)) {
            $this->attachment = $attachment;
        } else {
            throw new AdminNotFoundException(
                sprintf(
                    'Attachment with id ‘%s’ not found.',
                    $id
                )
            );
        }
    }

    // build phase

    protected function buildInternal()
    {
        set_time_limit(0);

        $this->attachment->setFileBase($this->getFileBase());
        $file_path = $this->attachment->getFilePath();

        if (!is_readable($file_path)) {
            throw new AdminNotFoundException(
                sprintf(
                    'Could not read attachment ‘%s’',
                    $file_path
                )
            );
        }

        // flush all and end buffering
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // display headers
        foreach ($this->attachment->getHttpHeaders() as $key => $value) {
            header(
                sprintf(
                    '%s: %s',
                    $key,
                    $value
                )
            );
        }

        // send headers first
        flush();

        // dump file contents
        readfile($file_path);
        flush();

        exit;
    }
}
