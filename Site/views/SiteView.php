<?php

/**
 * Base class for Site object views
 *
 * Views are composed of parts. The display of each part may be controlled
 * separately using the {@link SiteView::setPartMode()} method. For example,
 * An author view object would have separate parts for the author's name and
 * the author's description. A list of available parts may be retrieved with
 * the {@link SiteView::getParts()} method.
 *
 * The usage pattern of SiteView objects is as follows:
 *
 * 1. Instantiate a view object.
 * 2. Set which parts you want to be displayed and how the parts should be
 *    displayed using the <i>setPartMode()</i> methods on the instantiated
 *    view object.
 * 3. Display one or more objects using the view by calling the
 *    {@link SiteView::display()} method and passing in the object to be
 *    displayed.
 *
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteView
{
	// {{{ class constants

	/**
	 * Do not show the part. All parts should support this mode.
	 */
	const MODE_NONE    = 1;

	/**
	 * Show a shortened or summarized version of the part. Not all parts
	 * support this mode. If it is unsupported, it is treated like
	 * {@link SiteView::MODE_ALL}.
	 */
	const MODE_SUMMARY = 2;

	/**
	 * Show all of the part. This is the default mode for most parts.
	 */
	const MODE_ALL     = 3;

	// }}}
	// {{{ protected properties

	/**
	 * The application to which this view belongs
	 *
	 * @var SiteApplication
	 *
	 * @see SitePostView::__construct()
	 */
	protected $app;

	/**
	 * Path prefix for relatively referenced paths
	 *
	 * @var string
	 */
	protected $path_prefix = '';

	/**
	 * Array of parts of this view
	 *
	 * The part name is the array key and the array value is a two-element
	 * array containing a <i>mode</i> element and a <i>link</i> element for
	 * the part mode and part link respectively.
	 *
	 * @var array
	 *
	 * @see SiteView::definePart()
	 */
	protected $parts = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new Site object view
	 *
	 * @param SiteApplication $app the application to which this view belongs.
	 */
	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
		$this->define();
	}

	// }}}
	// {{{ public function setPathPrefix()

	/**
	 * Sets the prefix to use for relatively referenced paths such as file
	 * download links.
	 *
	 * @param string A relative path prefix to access the web root such as
	 *               “<code>../</code>”
	 */
	public function setPathPrefix($path_prefix)
	{
		$this->path_prefix = $path_prefix;
	}

	// }}}
	// {{{ public function setPartMode()

	/**
	 * Sets the display mode and optinoally the link value of a part in this
	 * view
	 *
	 * @param string $part the name of the part for which to set the mode.
	 * @param integer $mode the display mode of the part. One of
	 *                 {@link SiteView::MODE_ALL},
	 *                 {@link SiteView::MODE_SUMMARY} or
	 *                 {@link SiteView::MODE_NONE}. If an invalid mode is
	 *                 specified, SiteView::MODE_ALL is used.
	 * @param string|boolean $link optional. The link mode of the part. If
	 *                              false, the part will not be linked. If true
	 *                              the part will be linked using the default
	 *                              behaviour. If a string, the part will be
	 *                              linked using the specified string as the
	 *                              URI. If not specified, defaults to
	 *                              <i>true</i>.
	 *
	 * @throws InvalidArgumentException if the specified part name is invalid.
	 */
	public function setPartMode($part, $mode, $link = true)
	{
		if (!array_key_exists($part, $this->parts)) {
			throw new InvalidArgumentException(sprintf(
				'Specified part name “%s” is not valid for the %s view.',
				$part, get_class($this)));
		}

		$mode = $this->filterMode($mode);
		$link = $this->filterLink($link);
		$this->parts[$part] = array('mode' => $mode, 'link' => $link);
	}

	// }}}
	// {{{ public function getParts()

	/**
	 * Gets a list of the available parts of this view
	 *
	 * @return array the available parts of this view.
	 */
	public function getParts()
	{
		return array_keys($this->parts);
	}

	// }}}
	// {{{ abstract public function display()

	abstract public function display($object);

	// }}}
	// {{{ protected function getMode()

	/**
	 * Gets the display mode of a part in this view
	 *
	 * @param string $part the part for which to get the display mode.
	 *
	 * @return integer the display mode of the part. One of
	 *                 {@link SiteView::MODE_ALL},
	 *                 {@link SiteView::MODE_SUMMARY} or
	 *                 {@link SiteView::MODE_NONE}.
	 *
	 * @throws InvalidArgumentException if the specified part name is not
	 *                                  valid for this view.
	 *
	 * @see SiteView::setPartMode()
	 */
	protected function getMode($part)
	{
		if (!array_key_exists($part, $this->parts)) {
			throw new InvalidArgumentException(sprintf(
				'Specified part name “%s” is not valid for the %s view.',
				$part, get_class($this)));
		}

		return $this->parts[$part]['mode'];
	}

	// }}}
	// {{{ protected function getLink()

	/**
	 * Gets the link value of a part in this view
	 *
	 * @param string $part the part for which to get the link value.
	 *
	 * @return string|boolean the link value for the specified part. Either a
	 *                         boolean or a string containing a URI.
	 *
	 * @throws InvalidArgumentException if the specified part name is not
	 *                                  valid for this view.
	 *
	 * @see SiteView::setPartMode()
	 */
	protected function getLink($part)
	{
		if (!array_key_exists($part, $this->parts)) {
			throw new InvalidArgumentException(sprintf(
				'Specified part name “%s” is not valid for the %s view.',
				$part, get_class($this)));
		}

		return $this->parts[$part]['link'];
	}

	// }}}
	// {{{ protected function filterMode()

	/**
	 * Ensures the specified mode is a valid mode and makes it valid if
	 * invalid
	 *
	 * If an invalid mode is specified, {@link SiteView::MODE_ALL} is
	 * returned. Otherwise, the specified mode is returned.
	 *
	 * @param integer $mode the mode.
	 *
	 * @return integer a valid part mode.
	 */
	protected function filterMode($mode)
	{
		$valid_modes = array(
			self::MODE_ALL,
			self::MODE_SUMMARY,
			self::MODE_NONE,
		);

		if (!in_array($mode, $valid_modes)) {
			$mode = self::MODE_ALL;
		}

		return $mode;
	}

	// }}}
	// {{{ protected function filterLink()

	/**
	 * Ensures the specified link is valid for a view part and makes the link
	 * valid if invalid
	 *
	 * If an invalid link is specified, false is returned. Otherwise the
	 * specified link value is returned.
	 *
	 * @param boolean|string $link the link.
	 *
	 * @return boolean|string a valid link.
	 */
	protected function filterLink($link)
	{
		if (!is_bool($link) && !is_string($link)) {
			$link = false;
		}
		return $link;
	}

	// }}}
	// {{{ protected function define()

	/**
	 * Provides a location for view subclasses to define parts
	 *
	 * @see SiteView::definePart()
	 */
	protected function define()
	{
	}

	// }}}
	// {{{ protected function definePart()

	/**
	 * Defines a part in this view
	 *
	 * Parts are encouraged to use the default values for mode and link. By
	 * default, all parts are displayed. The calling code should selectivly
	 * turn off parts.
	 *
	 * @param string $part the name of the part.
	 * @param integer $mode optional. The part mode to use by default. Unless
	 *                       there is a good reason to do otherwise, the default
	 *                       {@link SiteView::MODE_ALL} should be used.
	 * @param strinjg|boolean $link optional. Whether or not the display the
	 *                               part as/with a link. Unless there is good
	 *                               reason to do otherwise, the default value
	 *                               of <i>true</i> should be used.
	 */
	protected function definePart($part, $mode = self::MODE_ALL, $link = true)
	{
		$mode = $this->filterMode($mode);
		$link = $this->filterLink($link);
		$this->parts[$part] = array('mode' => $mode, 'link' => $link);
	}

	// }}}
}

?>
