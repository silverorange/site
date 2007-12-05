<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Site/exceptions/SiteClassNotFoundException.php';
require_once 'Site/exceptions/SiteInvalidClassException.php';
require_once 'Swat/SwatMessage.php';

/**
 * Web application module for site messages
 *
 * This module works by adding {@link SwatMessage} objects to the session. As
 * such, it depends on the {@link SiteSessionModule}.
 *
 * @package   Site
 * @copyright 2004-2007
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMessagesModule extends SiteApplicationModule
{
	// {{{ protected properties

	/**
	 * Whether or not this messages module has been initialized with an active
	 * session
	 *
	 * @var boolean
	 */
	protected $session_is_initialized = false;

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
		if ($this->app->session->isActive())
			$this->initSession();
	}

    // }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The messages module depends on the SiteSessionModule feature.
	 *
	 * @return array an array of {@link SiteModuleDependency} objects defining
	 *                        the features this module depends on.
	 */
	public function depends()
	{
		return array(new SiteApplicationModuleDependency('SiteSessionModule'));
	}

	// }}}
	// {{{ public function registerMessageClass()

	/**
	 * Registers a custom message class with this module
	 *
	 * @param string $class_name the name of the custom message class.
	 * @param string $filename an optional filename of the file containing the
	 *                          class definition of the custom message class.
	 *
	 * @throws SiteClassNotFoundException if the the custom message class is
	 *                                     undefined and not found in the
	 *                                     optional class definition file.
	 * @throws SiteInvalidClassException if the custom message class is not
	 *                                    a {@link SwatMessage} or a subclass
	 *                                    of SwatMessage.
	 */
	public function registerMessageClass($class_name, $filename = null)
	{
		if (!class_exists($class_name) && $filename !== null)
			require_once $filename;

		if (!class_exists($class_name))
			throw new SiteClassNotFoundException(sprintf('Class ‘%s’ is not '.
				'defined and cannot be registered in this message module.',
				$class), 0, $class);

		if (strcmp($class_name, 'SwatMessage') != 0 &&
			!is_subclass_of($class_name, 'SwatMessage'))
			throw new SiteInvalidClassException(sprintf('Class ‘%s’ is not '.
				'a SwatMessage and cannot be registered in this message '.
				'module.', $class), 0, null);
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
		if (!$this->app->session->isActive())
			$this->app->session->activate();

		if (!$this->session_is_initialized)
			$this->initSession();

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
		$messages = array();

		if ($this->app->session->isActive()) {
			$messages = $this->app->session->messages;
			$this->app->session->messages = array();
		}

		return $messages;
	}

    // }}}
    // {{{ protected function initSession()

	/**
	 * Initializes session variables used by this module
	 */
	protected function initSession()
	{
		if (!$this->session_is_initialized &&
			(!isset($this->app->session->messages) ||
			get_class($this->app->session->messages) !== 'ArrayObject')) {
			$this->app->session->messages = new ArrayObject();
			$this->session_is_initialized = true;
		}
	}

    // }}}
}

?>
