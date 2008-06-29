<?php

require_once 'Swat/SwatObject.php';
require_once 'Swat/exceptions/SwatClassNotFoundException.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/SiteApplication.php';
require_once 'Site/dataobjects/SiteGadgetInstance.php';
require_once 'Site/SiteGadget.php';

/**
 * Handles creation of gadget objects
 *
 * A gadget object is created by specifying the gadget instance and application
 * in the {@link SiteGadgetFactory::get()} method. For example:
 *
 * <code>
 * $gadget_instance = ... ; // load a SiteGadgetInstance from the db
 * $gadget = SiteGadgetFactory::get($app, $gadget_instance);
 * $gadget->display();
 * </code>
 *
 * The factory can also be used inside an iteration of a
 * {@link SiteGadgetInstanceWrapper} object to quickly instantiate a set of
 * gadgets. For example:
 *
 * <code>
 * // load a set of SiteGadgetInstance objects from the db
 * $gadget_instances = ... ;
 * $gadgets = array(); // a place to keep the instantiated gadgets
 * foreach ($gadget_instances as $gadget_instance) {
 *     $gadgets[] = SiteGadgetFactory::get($app, $gadget_instance);
 * }
 * </code>
 *
 * A list of available gadgets may be retrieved for an application using the
 * {@link SiteGadgetFactory::getAvailable()} method.
 *
 * All gadget classes are loaded automatically from the gadget search path,
 * which is controlled by the {@link SiteGadgetFactory::addPath()} and
 * {@link SiteGadgetFactory::removePath()} methods. The relative search
 * path <code>'Site/gadgets'</code> is included by default.
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteGadgetFactory extends SwatObject
{
	// {{{ private static properties

	/**
	 * Paths to search for gadget files
	 *
	 * @var array
	 */
	private static $paths = array('Site/gadgets');

	/**
	 * Array of instantiated gadget objects
	 *
	 * This array is keyed on a hash of the SiteInstance and SiteApplication
	 * objects used to instantiate the gadget.
	 *
	 * @var array
	 */
	private static $gadgets = array();

	/**
	 * Cache of available gadgets
	 *
	 * This cache is cleared when the gadget path is updated. It is updated and
	 * used by the {@link SiteGadgetFactory::getAvailable()} method. When the
	 * value is null, this means the cache needs to be rebuilt.
	 *
	 * The array has the structure described in the return type documentaiton
	 * of the getAvailable() method.
	 *
	 * @var array
	 */
	private static $available_gadgets = null;

	// }}}
	// {{{ public static function get()

	/**
	 * Creates a gadget object from a gadget instance
	 *
	 * @param SiteApplication $app the application in which the gadget exists.
	 * @param SiteGadgetInstance the gadget instance used to create the gadget.
	 *
	 * @return SiteGadget a gadget object. The class of the gadget will depend
	 *                      on the gadget specified gadget instance.
	 *
	 * @throws SwatInvalidClassException if the gadget instance specifies an
	 *                                   invalid gadget class name, does not
	 *                                   specify a gadget class name or
	 *                                   specifies a gadget class name that is
	 *                                   not a subclass of SiteGadget.
	 * @throws SwatClassNotFoundException if the gadget instance specifies a
	 *                                    class that does not exist and could
	 *                                    not be found in the current gadget
	 *                                    search path.
	 */
	public static function get(SiteApplication $app,
		SiteGadgetInstance $instance)
	{
		$key = spl_object_hash($instance).spl_object_hash($app);

		if (!array_key_exists($key, self::$gadgets)) {
			$class_name = $instance->gadget;

			$valid_class_name = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/';
			if (preg_match($valid_class_name, $class_name) === 0) {
				throw new SwatInvalidClassException('Class "'.$class_name.'" '.
					'specified in gadget instance is not a valid class name.');
			}

			$include_paths = explode(PATH_SEPARATOR, get_include_path());

			// try to load class definition for the gadget class
			if (!class_exists($class_name) && count(self::$paths) > 0) {
				foreach (self::$paths as $path) {
					if ($path[0] == '/') {
						// path is absolute
						$filename = sprintf('%s/%s.php',
							$path, $class_name);

						if (file_exists($filename)) {
							require_once $filename;
							break;
						}
					} else {
						// path is relative to include path
						foreach ($include_paths as $include_path) {
							$filename = sprintf('%s/%s/%s.php',
								$include_path, $path, $class_name);

							if (file_exists($filename)) {
								require_once $filename;
								break 2;
							}
						}
					}
				}
			}

			if (!class_exists($class_name)) {
				throw new SwatClassNotFoundException(
					'No gadget of class "'.$class_name.'" exists.');
			}

			if (!is_subclass_of($class_name, 'SiteGadget')) {
				throw new SwatInvalidClassException(
					'Gadget class "'.$class_name.'" was found but is not a '.
					'subclass of SiteGadet.');
			}

			self::$gadgets[$key] = new $class_name($app, $instance);
		}

		return self::$gadgets[$key];
	}

	// }}}
	// {{{ public static function addPath()

	/**
	 * Adds a path to search for gadget files
	 *
	 * All gadgets in the search path will be loadable through the gadget
	 * factory. Paths added later are searched first.
	 *
	 * If the path is relative, the PHP include path is checked. The relative
	 * path 'Site/gadgets' is included by default.
	 *
	 * @param string $path the path to search for gadget files.
	 *
	 * @see SiteGadgetFactory::removePath()
	 */
	public static function addPath($path)
	{
		if (!in_array($path, self::$paths, true)) {
			self::$paths[] = $path;

			// clear available cache
			self::$available_gadgets = null;
		}
	}

	// }}}
	// {{{ public static function removePath()

	/**
	 * Removes a search path for gadget files
	 *
	 * @param string $path the path to remove.
	 *
	 * @see SiteGadgetFactory::addPath()
	 */
	public static function removePath($path)
	{
		$index = array_search($path, self::$paths);
		if ($index !== false) {
			array_splice(self::$paths, $index, 1);

			// clear available cache
			self::$available_gadgets = null;
		}
	}

	// }}}
	// {{{ public static function getAvailable()

	/**
	 * Gets a list of available gadgets
	 *
	 * This checks for gadget class definitions in all gadget paths.
	 *
	 * This method is intended to be used in administration applications where
	 * users will select and configure gadgets for their site instance.
	 *
	 * @param SiteApplication $app the application for which to get available
	 *                              gadgets.
	 *
	 * @return array an array of available gadgets with the array key being
	 *               the gadget class name the the array value being an object
	 *               of type stdClass with the following members:
	 *               - <i>string title</i>       the default title of the
	 *                                           gadget,
	 *               - <i>string class</i>       the class name of the gadget,
	 *               - <i>string description</i> the description of the gadget,
	 *                                           and
	 *               - <i>array settings</i>     an array of SiteGadgetSetting
	 *                                           objects available for the
	 *                                           gadget.
	 *
	 * @throws SwatClassNotFoundException if no suitable class exists for a
	 *                                    gadget definition file.
	 * @throws SwatInvalidClassException if a gadget definition file contains
	 *                                   a class that is not a subclass of
	 *                                   SiteGadget.
	 */
	public static function getAvailable(SiteApplication $app)
	{
		if (self::$available_gadgets === null) {
			self::$available_gadgets = array();
			$instance_class_name = SwatDBClassMap::get('SiteGadgetInstance');

			foreach (self::getGadgetFiles() as $file) {
				$filename = $file->getFilename();

				$class_name = substr($filename, 0, -4);

				if (!class_exists($class_name)) {
					require_once $file->getRealPath();
				}

				if (!class_exists($class_name)) {
					throw new SwatClassNotFoundException(
						'Gadget file "'.$filename.'" does not contain a class '.
						'"'.$class_name.'".');
				}

				if (!is_subclass_of($class_name, 'SiteGadget')) {
					throw new SwatInvalidClassException(
						'Gadget class "'.$class_name.'" was found but is not '.
						'a subclass of SiteGadet.');
				}

				$mock_instance = new $instance_class_name();
				$mock_instance->gadget = $class_name;

				$gadget = self::get($app, $mock_instance);

				$return_object = new stdClass();
				$return_object->title = $gadget->getTitle();
				$return_object->class = $class_name;
				$return_object->description = $gadget->getDescription();
				$return_object->settings = $gadget->getSettings();
				$return_object->ajax_proxy_map = $gadget->getAjaxProxyMap();

				self::$available_gadgets[$class_name] = $return_object;
			}
		}

		return self::$available_gadgets;
	}

	// }}}
	// {{{ private static function getGadgetFiles()

	/**
	 * Gets all gadget files from all gadget search paths
	 *
	 * Search paths that do not exist are ignored.
	 *
	 * @return array an array of SplFileInfo objects containing gadget class
	 *               definitions.
	 */
	private static function getGadgetFiles()
	{
		$files         = array();
		$include_paths = explode(PATH_SEPARATOR, get_include_path());
		$gadget_paths  = array();

		// make paths absolute, ignore non-existent paths
		foreach (self::$paths as $path) {
			if ($path[0] == '/') {
				if (is_dir($path)) {
					$gadget_paths[] = $path;
				}
			} else {
				foreach ($include_paths as $include_path) {
					$absolute_path = $include_path.'/'.$path;
					if (is_dir($absolute_path)) {
						$gadget_paths[] = $absolute_path;
						break;
					}
				}
			}
		}

		// find all php files in absolute paths
		foreach ($gadget_paths as $path) {
			$iterator = new DirectoryIterator($path);
			foreach ($iterator as $file) {
				// only include php files
				if (strncmp(strrev($file->getFilename()), 'php.', 4) === 0) {
					$files[] = $file->getFileInfo();
				}
			}
		}

		return $files;
	}

	// }}}
	// {{{ private function __construct()

	/**
	 * This class contains only static methods and should not be instantiated
	 */
	private function __construct()
	{
	}

	// }}}
}

?>
