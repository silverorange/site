<?php

require_once 'Site/pages/SitePage.php';
require_once 'Site/layouts/SiteXMLRPCServerLayout.php';
require_once 'XML/RPC2/Server.php';

/**
 * Base class for an XML-RPC Server
 *
 * The XML-RPC server acts as a regular page in an application. This means
 * all the regular page security features work for XML-RPC servers.
 *
 * Site XML-RPC server pages use the PEAR::XML_RPC2 package to service
 * requests.
 *
 * @package   Site
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteXMLRPCServer extends SitePage
{
	// {{{ public function init()

	/**
	 * @xmlrpc.hidden
	 */
	public function init()
	{
		parent::init();
	}

	// }}}
	// {{{ public function process()

	/**
	 * Process the request
	 *
	 * This method is called by site code to process the page request. It creates 
	 * an XML-RPC server and handles a request. The XML-RPC response from the
	 * server is output here as well.
	 *
	 * @xmlrpc.hidden
	 */
	public function process()
	{
		$server = XML_RPC2_Server::create($this, array('encoding' => 'UTF-8'));

		$this->layout->startCapture('response');
		$server->handleCall();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ public function build()

	/**
	 * @xmlrpc.hidden
	 */
	public function build()
	{
	}

	// }}}
	// {{{ public function finalize()

	/**
	 * @xmlrpc.hidden
	 */
	public function finalize()
	{
		parent::finalize();
	}

	// }}}
	// {{{ public function __toString()

	/**
	 * @xmlrpc.hidden
	 */
	public function __toString()
	{
		parent::__toString();
	}

	// }}}
	// {{{ protected function createLayout()

	/**
	 * @xmlrpc.hidden
	 */
	protected function createLayout()
	{
		return new SiteXMLRPCServerLayout($this->app);
	}

	// }}}
}

?>
