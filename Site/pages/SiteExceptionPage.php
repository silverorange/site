<?php

/**
 * A page to display exceptions.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteExceptionPage extends SitePage
{
    /**
     * @var SiteException
     */
    protected $exception;

    public function setException($exception)
    {
        if ($exception instanceof SwatException) {
            $this->exception = $exception;
        } else {
            $this->exception = new SiteException($exception);
        }
    }

    // init phase

    public function init()
    {
        parent::init();

        $this->initHttpStatusHeader();
    }

    protected function initHttpStatusHeader()
    {
        match ($this->getHttpStatusHeader()) {
            400     => header('HTTP/1.0 400 Bad Request'),
            401     => header('HTTP/1.0 401 Unauthorized'),
            403     => header('HTTP/1.0 403 Forbidden'),
            404     => header('HTTP/1.0 404 Not Found'),
            default => header('HTTP/1.0 500 Internal Server Error'),
        };
    }

    protected function getHttpStatusHeader()
    {
        return ($this->exception instanceof SiteException) ?
            $this->exception->http_status_code : 500;
    }

    // build phase

    protected function getTitle()
    {
        if ($this->exception === null) {
            $title = Site::_('Unknown Error');
        } elseif ($this->exception instanceof SiteException
            && $this->exception->title !== null) {
            $title = $this->exception->title;
        } else {
            $title = Site::_('Error');
        }

        return $title;
    }

    protected function getSummary()
    {
        return match ($this->getHttpStatusHeader()) {
            401     => Site::_('Sorry, you must log in to view this page.'),
            404     => Site::_('Sorry, we couldn’t find the page you were looking for.'),
            403     => Site::_('Sorry, the page you requested is not accessible.'),
            default => Site::_('Sorry, there was a problem loading the  page you requested.'),
        };
    }

    protected function getSuggestions()
    {
        return [];
    }
}
