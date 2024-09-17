<?php

/**
 * Base class for an XML-RPC Server.
 *
 * The XML-RPC server acts as a regular page in an application. This means
 * all the regular page security features work for XML-RPC servers.
 *
 * Site XML-RPC server pages use the PEAR::XML_RPC2 package to service
 * requests.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteXMLRPCServer extends SitePage
{
    /**
     * Process the request.
     *
     * This method is called by site code to process the page request. It creates
     * an XML-RPC server and handles a request. The XML-RPC response from the
     * server is output here as well.
     *
     * @xmlrpc.hidden
     */
    public function process()
    {
        $server = XML_RPC2_Server::create($this, [
            'encoding' => 'utf-8',
            'input'    => 'XML_RPC2_Server_Input_PhpInput',
            // Explicitly select the PHP backend. The native backend has
            // trouble with UTF-8 encoding and with properly parsing
            // method parameters.
            'backend' => 'Php',
        ]);

        $this->layout->startCapture('response');
        $server->handleCall();
        $this->layout->endCapture();
    }

    /**
     * @xmlrpc.hidden
     */
    public function __toString(): string
    {
        return parent::__toString();
    }

    /**
     * @xmlrpc.hidden
     */
    protected function createLayout()
    {
        return new SiteXMLRPCServerLayout($this->app);
    }
}
