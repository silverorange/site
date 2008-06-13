<?php

require_once 'Swat/SwatObject.php';
require_once 'Site/exceptions/SiteThemeException.php';

/**
 * Theme for a site
 *
 * @package   Site
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteThemeModule
 */
class SiteTheme extends SwatObject
{
	// {{{ protected properties

	/**
	 * Title of this theme
	 *
	 * @var string
	 *
	 * @see SiteTheme::getTitle()
	 */
	protected $title;

	/**
	 * Shortname of this theme
	 *
	 * The shortname is used to identify a particular theme. Shortnames should
	 * be all lowercase with multiple words separated by an underscore. Every
	 * theme should have a unique shortname as only one theme with a given
	 * shortname may be installed at one time.
	 *
	 * @var string
	 *
	 * @see SiteTheme::getShortname()
	 */
	protected $shortname;

	/**
	 * Description of this theme
	 *
	 * @var string
	 *
	 * @see SiteTheme::getDescription()
	 */
	protected $description;

	/**
	 * Author of this theme
	 *
	 * @var string
	 *
	 * @see SiteTheme::getAuthor()
	 */
	protected $author;

	/**
	 * Email contact for this theme
	 *
	 * @var string
	 *
	 * @see SiteTheme::getEmail()
	 */
	protected $email;

	/**
	 * License text or link for this theme
	 *
	 * This may be either a full license text or a link to a license. For
	 * example:
	 *
	 * <pre>
	 * http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
	 * </pre>
	 *
	 * @var string
	 */
	protected $license;

	/**
	 * Location of theme files
	 *
	 * @var string
	 */
	protected $directory;

	// }}}
	// {{{ private function __construct()

	/**
	 * Themes should only be instantiated through the {@link SiteTheme::load()}
	 * and {@link SiteTheme::loadXml()} methods.
	 */
	private function __construct()
	{
	}

	// }}}

	// accessors
	// {{{ public function getTitle()

	/**
	 * Gets the title of this theme
	 *
	 * @return string the title of this theme.
	 */
	public function getTitle()
	{
		return strval($this->title);
	}

	// }}}
	// {{{ public function getShortname()

	/**
	 * Gets the shortname of this theme
	 *
	 * The shortname is used to identify a particular theme. Shortnames should
	 * be all lowercase with multiple words separated by an underscore. Every
	 * theme should have a unique shortname as only one theme with a given
	 * shortname may be installed at one time.
	 *
	 * The camel-case variant of a theme shortname is formed by upper-casing
	 * any letter that follows an underscore, upper-casing the first letter
	 * and then removing all underscores. For example, a theme with the
	 * shortname <em>ceo_blues</em> would have a camel-case variant of
	 * <em>CeoBlues</em>. The camel-case variant is used in some places in the
	 * theme specification.
	 *
	 * @param boolean $camel_case optional. Whether or not to return the
	 *                             camel-case variant of this theme's shortname.
	 *                             Defaults to false.
	 *
	 * @return string the shortname of this theme.
	 */
	public function getShortname($camel_case = false)
	{
		$shortname = strval($this->shortname);

		if ($camel_case) {
			$shortname = str_replace('_', ' ', $shortname);
			$shortname = str_replace('.', ' ', $shortname);
			$shortname = str_replace('-', ' ', $shortname);

			$shortname = str_replace(' ', '', ucwords($shortname));
		}

		return $shortname;
	}

	// }}}
	// {{{ public function getDescription()

	/**
	 * Gets the description of this theme
	 *
	 * @return string the description of this theme.
	 */
	public function getDescription()
	{
		return strval($this->description);
	}

	// }}}
	// {{{ public function getAuthor()

	/**
	 * Gets the author of this theme
	 *
	 * @return string the author of this theme.
	 */
	public function getAuthor()
	{
		return strval($this->author);
	}

	// }}}
	// {{{ public function getEmail()

	/**
	 * Gets the email of this theme
	 *
	 * @return string the email of this theme.
	 */
	public function getEmail()
	{
		return strval($this->email);
	}

	// }}}
	// {{{ public function getLicense()

	/**
	 * Gets the license of this theme
	 *
	 * @return string the license of this theme.
	 */
	public function getLicense()
	{
		return strval($this->license);
	}

	// }}}

	// file accessors
	// {{{ public function getLayoutClass()

	public function getLayoutClass()
	{
		$class_name = $this->getShortname(true).'Layout';
		$filename   = 'layouts/'.$class_name.'.php';

		if ($this->fileExists($filename)) {
			require_once $this->directory.'/'.$filename;
			if (!class_exists($class_name)) {
				throw new SiteThemeException(sprintf('Theme layout file "%s" '.
					'must contain a class named "%s"',
					$filename, $class_name));
			}
		} else {
			$class_name = null;
		}

		return $class_name;
	}

	// }}}
	// {{{ public function getTemplateFile()

	public function getTemplateFile()
	{
		$filename = 'layouts/xhtml/template.php';

		if ($this->fileExists($filename)) {
			$filename = $this->directory.'/'.$filename;
		} else {
			$filename = null;
		}

		return $filename;
	}

	// }}}
	// {{{ public function getCssFile()

	public function getCssFile()
	{
		$filename = 'www/styles/theme.css';

		if ($this->fileExists($filename)) {
			// web-accessible version
			$filename = 'themes/'.$this->getShortname().'/styles/theme.css';
		} else {
			$filename = null;
		}

		return $filename;
	}

	// }}}
	// {{{ public function getFaviconFile()

	public function getFaviconFile()
	{
		$filename = 'www/favicon.ico';

		if ($this->fileExists($filename)) {
			// web-accessible version
			$filename = 'themes/'.$this->getShortname().'/favicon.ico';
		} else {
			$filename = null;
		}

		return $filename;
	}

	// }}}
	// {{{ public function fileExists()

	/**
	 * Checks whether or not a file exists for this theme
	 *
	 * @param string $filename the file to check. If the filename is not
	 *                          absolute, it is checked relative to this theme's
	 *                          directory.
	 *
	 * @return boolean true if the file exists for this theme and false if
	 *                 not.
	 *
	 * @see SiteTheme::$directory
	 */
	public function fileExists($filename)
	{
		// normalize Windows paths to UNIX
		$filename = end(explode(':', $filename, 2));
		$filename = str_replace('\\', '/', $filename);

		// check if file is absolute or relative to the theme directory
		if ($filename[0] != '/') {
			$filename = $this->directory.'/'.$filename;
		}

		return file_exists($filename);
	}

	// }}}

	// loading
	// {{{ public static function load()

	/**
	 * Loads a theme from a manifest file
	 *
	 * @param string $filename the manifest file of the theme.
	 *
	 * @return SiteTheme the loaded theme.
	 *
	 * @throws SiteThemeException if the manifest file is invalid.
	 */
	public static function load($filename)
	{
		$errors = libxml_use_internal_errors(true);

		$document = new DOMDocument();
		$document->load($filename);

		$xml_errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors($errors);

		if (count($xml_errors) > 0) {
			$message = '';

			foreach ($xml_errors as $error) {
				$message.= sprintf("%s in %s, line %d\n",
					trim($error->message),
					$error->file,
					$error->line);
			}

			throw new SiteThemeException("Invalid theme manifest:\n".$message);
		}

		// parse manifest file
		$theme = self::parseTheme($document);

		// set theme directory
		$directory = dirname(realpath($filename));

		// normalize Windows paths to UNIX format
		$directory = end(explode(':', $directory, 2));
		$directory = str_replace('\\', '/', $directory);

		$theme->directory = $directory;

		echo $theme;

		return $theme;
	}

	// }}}
	// {{{ public static function loadXml()

	/**
	 * Loads a theme from an XML string containg a theme manifest
	 *
	 * @param string $xml the manifest XML of the theme.
	 * @param string $directory the directory containing theme files.
	 *
	 * @return SiteTheme the loaded theme.
	 *
	 * @throws SiteThemeException if the manifest XML is invalid.
	 */
	public static function loadXml($xml, $directory)
	{
		$errors = libxml_use_internal_errors(true);

		$document = new DOMDocument();
		$document->loadXML($xml);

		$xml_errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors($errors);

		if (count($xml_errors) > 0) {
			$message = '';

			foreach ($xml_errors as $error) {
				$message.= sprintf("%s in %s, line %d\n",
					trim($error->message),
					$error->file,
					$error->line);
			}

			throw new SiteThemeException("Invalid theme manifest:\n".$message);
		}

		$theme = self::parseTheme($document);
		$theme->directory = strval($directory);

		return $theme;
	}

	// }}}
	// {{{ private static function parseTheme()

	private static function parseTheme(DOMDocument $document)
	{
		if ($document->documentElement->nodeName !== 'theme') {
			throw new SiteThemeException(
				'Root element of theme manifest must be \'theme\'.');
		}

		$theme = new SiteTheme();

		// parse manifest elements into theme properties
		foreach ($document->documentElement->childNodes as $node) {
			if ($node->nodeType === XML_ELEMENT_NODE) {
				switch ($node->nodeName) {
				case 'title':
					$theme->title = $node->nodeValue;
					break;

				case 'shortname':
					$theme->shortname = $node->nodeValue;
					break;

				case 'description':
					$theme->description = $node->nodeValue;
					break;

				case 'author':
					$theme->author = $node->nodeValue;
					break;

				case 'email':
					$theme->email = $node->nodeValue;
					break;

				case 'license':
					$theme->license= $node->nodeValue;
					break;

				default:
					break;
				}
			}
		}

		// Make sure manifest has all required fields
		$required_elements = array(
			'title',
			'shortname',
			'description',
			'author',
			'license',
		);

		foreach ($required_elements as $name) {
			if ($theme->$name === null) {
				throw new SiteThemeException(sprintf(
					"Required theme manifest element '%s' is missing.",
					$name));
			}
		}

		return $theme;
	}

	// }}}
}

?>
