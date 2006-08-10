<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Swat/SwatMessage.php';

/**
 * Web application module for site messages
 *
 * This module works by adding {@link SwatMessage} objects to the session. As
 * such, it depends on the {@link SiteSessionModule}.
 *
 * @package   Site
 * @copyright 2004-2006
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMessagesModule extends SiteApplicationModule
{
	// {{{ public function __construct()

	/**
	 * Creates a new site messages module
	 *
	 * @param SiteApplication $app the application this module belongs to.
	 *
	 * @throws SiteException if there is no session module loaded the messages
	 *                        module throws an exception.
	 *
	 */
	public function __construct(SiteApplication $app)
	{
		if (!(isset($app->session) &&
			$app->session instanceof SiteSessionModule))
			throw new SiteException('The SiteMessagesModule requires a '.
				'SiteSessionModule to be loaded. Please either explicitly '.
				'add a session module to the application before '.
				'instantiating the messages module, or specify the session '.
				'module before the messages module in the application\'s '.
				'getDefaultModuleList() method.');

		parent::__construct($app);
	}

	// }}}
    // {{{ public function init()

	/**
	 * Initializes this messages module
	 *
	 * If there are no messages defined in the session, the messages are
	 * defined as an empty list.
	 */
	public function init()
	{
		if (!isset($this->app->session->messages) ||
			!is_array($this->app->session->messages))
			$this->app->session->messages = array();
	}

    // }}}
    // {{{ public function add()

	/**
	 * Adds a message to this module
	 *
	 * @param SwatMessage $message the message to add.
	 */
	public function add(SwatMessage $message)
	{
		$this->app->session->messages[] = $message;
	}

    // }}}
    // {{{ public function getAll()

	/**
	 * Gets all messages from this module
	 *
	 * After this method runs, the messages are cleared from the module.
	 *
	 * @return array the array of SwatMessage objects in this module.
	 */
	public function &getAll()
	{
		$messages = $this->app->session->messages;
		$this->app->session->messages = array();
		return $messages;
	}

    // }}}
}

?>
