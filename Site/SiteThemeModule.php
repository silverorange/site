<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Site/exceptions/SiteThemeException.php';
require_once 'Site/SiteTheme.php';

/**
 * Web application module for handling themeing
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteThemeModule extends SiteApplicationModule
{
	// {{{ protected properties

	/**
	 * List of paths to search for themes
	 *
	 * By defualt, the relative path '../themes' exists.
	 *
	 * @var array
	 *
	 * @see SiteThemeModule::addPath()
	 */
	protected $theme_paths = array('../themes');

	/**
	 * The current theme
	 *
	 * Null if there is no current theme.
	 *
	 * @var SiteTheme
	 */
	protected $theme;

	// }}}
	// {{{ public function init()

	public function init()
	{
	}

	// }}}
	// {{{ public function set()

	/**
	 * Sets the theme to use
	 *
	 * @param string $theme the shortname of the theme to use. Theme names may
	 *                       contain letters, numbers, underscores, dashes and
	 *                       periods.
	 *
	 * @throws InvalidArgumentException if the theme name is not a valid theme
	 *                                  name.
	 */
	public function set($shortname)
	{
		if (preg_match('/^[a-z0-9._-]+$/u', $shortname) === 0) {
			throw new InvalidArgumentException(sprintf('Theme shortname "%s" '.
				'is not a valid theme shortname.'));
		}

		$manifest = $this->findManifest($shortname);
		if ($manifest === null) {
			throw new SiteThemeException('No theme with the shortname "%s" '.
				'is installed.');
		}

		$this->theme = SiteTheme::load($manifest);
	}

	// }}}
	// {{{ public function getThemes()

	/**
	 * Gets a list of all installed themes
	 *
	 * @return array an array of SiteTheme objects.
	 */
	public function getThemes()
	{
		$themes = array();

		// get absolute paths for all theme paths
		$include_paths = explode(PATH_SEPARATOR, get_include_path());
		$theme_paths  = array();
		foreach ($this->theme_paths as $theme_path) {
			// normalize Windows paths to UNIX format
			$theme_path = end(explode(':', $theme_path, 2));
			$theme_path = str_replace('\\', '/', $theme_path);

			// check if path is absolute or relative to the include path
			if ($theme_path[0] == '/') {
				if (is_dir($theme_path)) {
					$theme_paths[] = $theme_path;
				}
			} else {
				foreach ($include_paths as $include_path) {
					if (is_dir($include_path.'/'.$theme_path)) {
						$theme_paths[] = $include_path.'/'.$theme_path;
						break;
					}
				}
			}
		}

		// search each theme path for theme manifest files
		foreach ($theme_paths as $path) {
			$dir = new DirectoryIterator($path);
			foreach ($dir as $file) {
				if ($file->isDir()) {
					$theme_dir = new DirectoryIterator($dir->getRealPath());
					foreach ($theme_dir as $file) {
						if ($file->getFilename() === 'manifest.xml') {
							$themes[] = SiteTheme::load($file->getRealPath());
							break;
						}
					}
				}
			}
		}

		return $themes;
	}

	// }}}
	// {{{ public function addPath()

	/**
	 * Adds a theme path to this module
	 *
	 * All theme paths are relative to the PHP include path. The relative path
	 * '../themes' is included by default.
	 *
	 * @param string $path the theme path to add.
	 *
	 * @see SiteThemeModule::removePath()
	 */
	public function addPath($path)
	{
		if (!in_array($path, $this->theme_paths, true)) {
			// add path to front of array since it is more likely we will find
			// theme files in manually added paths
			array_unshift($this->theme_paths, $path);
		}
	}

	// }}}
	// {{{ public function removePath()

	/**
	 * Removes a theme path from this module
	 *
	 * @param string $path the path to remove.
	 *
	 * @see SiteThemeModule::addPath()
	 */
	public function removePath($path)
	{
		$index = array_search($path, $this->theme_paths);
		if ($index !== false) {
			array_splice($this->theme_paths, $index, 1);
		}
	}

	// }}}
	// {{{ public function getLayoutClass()

	public function getLayoutClass($base_layout_class)
	{
		$class = ($this->theme instanceof SiteTheme) ?
			$this->theme->getLayoutClass() : null;

		if ($class === null) {
			$class = $base_layout_class;
		} elseif (!is_subclass_of($class, $base_layout_class)) {
			throw new SiteException(sprintf('Layout class "%s" must '.
				'be a subclass of %s.',
				$class, $base_layout_class));
		}

		return $class;
	}

	// }}}
	// {{{ public function getTemplateFile()

	public function getTemplateFile($default_template)
	{
		$filename = ($this->theme instanceof SiteTheme) ?
			$this->theme->getTemplateFile() : null;

		if ($filename === null) {
			$filename = $default_template;
		}

		return $filename;
	}

	// }}}
	// {{{ public function getCssFile()

	public function getCssFile()
	{
		return ($this->theme instanceof SiteTheme) ?
			$this->theme->getCssFile() : null;
	}

	// }}}
	// {{{ public function getFaviconFile()

	public function getFaviconFile()
	{
		return ($this->theme instanceof SiteTheme) ?
			$this->theme->getFaviconFile() : null;
	}

	// }}}
	// {{{ protected function findManifest()

	/**
	 * Checks whether or not a file exists for the current theme
	 *
	 * This checks all the theme paths relative to the include path.
	 *
	 * @return string the full filename if the file exists for the current
	 *                theme and null if no such file exists for the current
	 *                theme.
	 */
	protected function findManifest($shortname)
	{
		$filename      = null;
		$include_paths = explode(PATH_SEPARATOR, get_include_path());

		foreach ($this->theme_paths as $theme_path) {
			// normalize Windows paths to UNIX format
			$theme_path = end(explode(':', $theme_path, 2));
			$theme_path = str_replace('\\', '/', $theme_path);

			// check if path is absolute or relative to the include path
			if ($theme_path[0] == '/') {
				$temp_filename = $theme_path.'/'.$shortname.'/manifest.xml';
				if (file_exists($temp_filename)) {
					$filename = $temp_filename;
					break;
				}
			} else {
				foreach ($include_paths as $include_path) {
					$temp_filename = $include_path.'/'.$theme_path.'/'.
						$shortname.'/manifest.xml';

					if (file_exists($temp_filename)) {
						$filename = $temp_filename;
						break 2;
					}
				}
			}
		}

		return $filename;
	}

	// }}}
}

?>
