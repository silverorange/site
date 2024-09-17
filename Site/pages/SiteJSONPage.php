<?php

/**
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteJSONPage extends SitePage
{
    public function __construct(
        SiteApplication $app,
        ?SiteLayout $layout = null,
        array $arguments = []
    ) {
        parent::__construct($app, $layout, $arguments);

        $this->setLayout(
            new SiteLayout(
                $this->app,
                SiteJSONTemplate::class
            )
        );
    }

    // build phase

    public function build()
    {
        $this->layout->data->content = json_encode($this->getResponse());
    }

    abstract protected function getResponse();

    protected function getStatus($code = 'ok', $message = '')
    {
        return ['code' => $code, 'message' => $message];
    }
}
