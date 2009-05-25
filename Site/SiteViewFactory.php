<?php

require_once 'Swat/exceptions/SwatClassNotFoundException.php';

/**
 * Factory for creating object views
 *
 * This factory provides an easy method for overriding the default object views
 * provided by Site or other packages.
 *
 * Example usage:
 *
 * <code>
 * <?php
 *
 * // get a new comment view
 * $comment_view = SiteViewFactory::get($this->app, 'comment');
 *
 * // register a new view class for comments
 * SiteViewFactory::registerView('comment', 'MyCommentView');
 *
 * // create a new comment view of class 'MyCommentView'
 * $comment_view = SiteViewFactory::get($this->app, 'comment');
 *
 * ?>
 * </code>
 *
 * When an undefined view class is requested, the factory attempts to find and
 * require a class-definition file for the view class using the factory search
 * path. All search paths are relative to the PHP include path. The search path
 * '<code>Site/views</code>' is included by default. Search paths can be added
 * and removed using the {@link SiteViewFactory::addPath()} and
 * {@link SiteViewFactory::removePath()} methods.
 *
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteView
 */
class SiteViewFactory extends SwatObject
{
	// {{{ private static properties

	/**
	 * List of registered view classes indexed by the view type
	 *
	 * @var array
	 */
	private static $view_class_names_by_type = array();

	/**
	 * Paths to search for class-definition files
	 *
	 * @var array
	 */
	private static $search_paths = array('Site/views');

	// }}}
	// {{{ public static function get()

	/**
	 * Gets a view of the specified type
	 *
	 * @param SiteApplication $app the application in which to get the view.
	 * @param string $type the type of view to get. There must be a view class
	 *                      registered for this type.
	 *
	 * @return SiteView the view of the specified type. The view will be an
	 *                    instance of whatever class was registered for the
	 *                    view type.
	 *
	 * @throws InvalidArgumentException if there is no view registered for the
	 *                                  requested <i>$type</i>.
	 */
	public static function get(SiteApplication $app, $type)
	{
		$type = strval($type);
		if (!array_key_exists($type, self::$view_class_names_by_type)) {
			throw new InvalidArgumentException(sprintf(
				'No views are registered with the type "%s".',
				$type));
		}

		$view_class_name = self::$view_class_names_by_type[$type];
		self::loadViewClass($view_class_name);

		return new $view_class_name($app);
	}

	// }}}
	// {{{ public static function registerView()

	/**
	 * Registers a view class with the factory
	 *
	 * View classes must be registed with the factory before they are used.
	 * When a view class is registered for a particular type, an instance of
	 * the view class is returned whenever a view of that type is requested.
	 *
	 * @param string $type the view type.
	 * @param string $view_class_name the class name of the view. The class
	 *                                 does not need to be defined until a
	 *                                 view of the specified type is requested.
	 */
	public static function registerView($type, $view_class_name)
	{
		$type = strval($type);
		self::$view_class_names_by_type[$type] = $view_class_name;
	}

	// }}}
	// {{{ public static function addPath()

	/**
	 * Adds a search path for class-definition files
	 *
	 * When an undefined view class is requested, the factory attempts to find
	 * and require a class-definition file for the view class.
	 *
	 * All search paths are relative to the PHP include path. The search path
	 * '<code>Site/views</code>' is included by default.
	 *
	 * @param string $search_path the path to search for view class-definition
	 *                             files.
	 *
	 * @see SiteViewFactory::removePath()
	 */
	public static function addPath($search_path)
	{
		if (!in_array($search_path, self::$search_paths, true)) {
			// add path to front of array since it is more likely we will find
			// class-definitions in manually added search paths
			array_unshift(self::$search_paths, $search_path);
		}
	}

	// }}}
	// {{{ public static function removePath()

	/**
	 * Removes a search path for view class-definition files
	 *
	 * @param string $path the path to remove.
	 *
	 * @see SiteViewFactory::addPath()
	 */
	public static function removePath($path)
	{
		$index = array_search($path, self::$paths);
		if ($index !== false) {
			array_splice(self::$paths, $index, 1);
		}
	}

	// }}}
	// {{{ private static function loadViewClass()

	/**
	 * Loads a view class-definition if it is not defined
	 *
	 * This checks the factory search path for an appropriate source file.
	 *
	 * @param string $view_class_name the name of the view class.
	 *
	 * @throws SwatClassNotFoundException if the view class is not defined and
	 *                                    no suitable file in the view search
	 *                                    path contains the class definition.
	 */
	private static function loadViewClass($view_class_name)
	{
		// try to load class definition for $view_class_name
		if (!class_exists($view_class_name) &&
			count(self::$search_paths) > 0) {
			$include_paths = explode(':', get_include_path());
			foreach (self::$search_paths as $search_path) {
				// check if search path is relative
				if ($search_path[0] == '/') {
					$filename = sprintf('%s/%s.php',
						$search_path, $view_class_name);

					if (file_exists($filename)) {
						require_once $filename;
						break;
					}
				} else {
					foreach ($include_paths as $include_path) {
						$filename = sprintf('%s/%s/%s.php',
							$include_path, $search_path, $view_class_name);

						if (file_exists($filename)) {
							require_once $filename;
							break 2;
						}
					}
				}
			}
		}

		if (!class_exists($view_class_name)) {
			throw new SwatClassNotFoundException(sprintf(
				'View class "%s" does not exist and could not be found in '.
				'the search path.',
				$view_class_name), 0, $view_class_name);
		}
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
