<?php

/**
 * @copyright 2006-2016 silverorange
 */
class SiteXMLRPCServerFactory extends SitePageFactory
{
    /**
     * @throws SiteNotFoundException
     * @throws SiteClassNotFoundException
     */
    public function resolvePage(string $source, ?SiteLayout $layout = null): SiteAbstractPage
    {
        $layout ??= $this->getLayout($source);
        $map = $this->getPageMap();

        if (!isset($map[$source])) {
            throw new SiteNotFoundException();
        }

        $class = $map[$source];

        return $this->instantiatePage($class, $layout);
    }

    protected function getPageMap(): array
    {
        return [];
    }

    protected function getLayout($source): SiteXMLRPCServerLayout
    {
        return new SiteXMLRPCServerLayout($this->app);
    }
}
