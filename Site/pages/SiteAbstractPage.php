<?php

require_once 'Site/layouts/SiteLayout.php';
require_once 'Site/SiteObject.php';
require_once 'Site/SiteApplication.php';

/**
 * Base class for pages
 *
 * @package   Site
 * @copyright 2004-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteAbstractPage extends SiteObject
{
	// {{{ public properties

	/**
	 * The application to which this page belongs
	 *
	 * Note: Ideally, this property would be protected. It is public to
	 * maintain backwards compatibility.
	 *
	 * @var SiteApplication
	 *
	 * @see SitePage::__construct()
	 */
	public $app;

	/**
	 * The layout of this page
	 *
	 * @var SiteLayout
	 *
	 * Note: Ideally, this property would be protected and have a public
	 * accessor method. It is public to maintain backwards compatibility.
	 *
	 * @see SitePage::__construct()
	 * @see SitePage::createLayout()
	 * @see SiteAbstractPage::setLayout()
	 */
	public $layout;

	// }}}
	// {{{ protected properties

	/**
	 * The source string of this page
	 *
	 * This is the Apache rewritten query string passed to the page factory. It
	 * is the visible part of the URL after the base href and excluding
	 * additional query parameters.
	 *
	 * @var string
	 */
	protected $source = null;

	/**
	 * Additional arguments as passed to the constructor
	 *
	 * These are saved in a protected variable so page decorators can access
	 * the arugments.
	 *
	 * @var array
	 *
	 * @see SiteAbstractPage::getArgument()
	 * @see SiteAbstractPage::getArgumentMap()
	 * @see SitePage::__construct()
	 */
	protected $arguments = array();

	// }}}
	// {{{ public function getSource()

	/**
	 * Gets the source string of this page
	 *
	 * This is the Apache rewritten query string passed to the page factory. It
	 * is the visible part of the URL after the base href and excluding
	 * additional query parameters.
	 *
	 * @return string the source string of this page.
	 *
	 * @xmlrpc.hidden
	 */
	public function getSource()
	{
		return $this->source;
	}

	// }}}
	// {{{ public function setSource()

	/**
	 * Sets the source string of this page
	 *
	 * This is the Apache rewritten query string passed to the page factory. It
	 * is the visible part of the URL after the base href and excluding
	 * additional query parameters.
	 *
	 * Note: Ideally, the source string would be set in the constructor of
	 * this class and would only have a public accessor method. A setter
	 * method exists here for backwards compatibility.
	 *
	 * @param string $source
	 *
	 * @xmlrpc.hidden
	 */
	public function setSource($source)
	{
		$this->source = $source;
	}

	// }}}
	// {{{ protected function setLayout()

	/**
	 * @param SiteLayout $layout
	 */
	protected function setLayout(SiteLayout $layout)
	{
		$this->layout = $layout;
	}

	// }}}
	// {{{ protected function getArgument()

	protected function getArgument($name)
	{
		$value = null;

		$map = $this->getArgumentMap();
		if (array_key_exists($name, $map)) {
			$key     = $map[$name][0];
			$default = $map[$name][1];
			if (array_key_exists($key, $this->arguments)) {
				$value = $this->arguments[$key];
			} else {
				$value = $default;
			}
		}

		return $value;
	}

	// }}}
	// {{{ protected function getArgumentMap()

	/**
	 * Returns an array of the form:
	 *
	 * <code>
	 * array(
	 *    $argument_name => array($position, $default_value),
	 * );
	 * </code>
	 *
	 * @return array
	 */
	protected function getArgumentMap()
	{
		return array();
	}

	// }}}

	// init phase
	// {{{ public function init()

	/**
	 * This is the first page-level method that is called by a
	 * {@link SiteWebApplication}. Runs after {@link SiteLayout::init()}. Runs
	 * before {@link SiteLayout::process()}. This method is intended to
	 * initialize objects used by the {@link SiteAbstractPage::process()} and
	 * {@link SiteAbstractPage::build()} methods.
	 *
	 * @xmlrpc.hidden
	 */
	public function init()
	{
	}

	// }}}

	// process phase
	// {{{ public function process()

	/**
	 * Runs after {@link SiteAbstractPage::init()} and
	 * {@link SiteLayout::process()}. Runs before
	 * {@link SiteLayout::build()}. This method is intended to process
	 * data entered by the user.
	 *
	 * @xmlrpc.hidden
	 */
	public function process()
	{
	}

	// }}}

	// build phase
	// {{{ public function build()

	/**
	 * Runs after {@link SiteAbstractPage::process()} and
	 * {@link SiteLayout::build()}. Runs before
	 * {@link SiteLayout::finalize()}. This method is intended to build page
	 * content and add it to the layout.
	 *
	 * @xmlrpc.hidden
	 */
	public function build()
	{
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	/**
	 * Runs after {@link SiteAbstractPage::build()} and
	 * {@SiteLayout::finalize()}. Runs before
	 * {@link SiteLayout::complete()}. This method is intended to add HTML head
	 * entries or perform other actions that should happen after the page has
	 * been built.
	 *
	 * @xmlrpc.hidden
	 */
	public function finalize()
	{
	}

	// }}}
}

?>
