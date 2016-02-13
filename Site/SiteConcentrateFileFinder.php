<?php

require_once 'Concentrate/DataProvider/FileFinderInterface.php';
require_once 'Concentrate/DataProvider/FileFinderDirectory.php';

/**
 * @package   Site
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteConcentrateFileFinder
	implements Concentrate_DataProvider_FileFinderInterface
{
	// {{{ public function getDataFiles()

	public function getDataFiles()
	{
		$files = array();

		if (preg_match('!pear/lib$!', get_include_path()) === 1) {
			// Load data files from PEAR data directories and from other
			// include paths.
			foreach ($this->getIncludeDirs() as $include_dir) {
				if (preg_match('!pear/lib$!', $include_dir) === 1) {
					$files = array_merge(
						$this->getPearDataFiles($include_dir),
						$files
					);
				} else {
					$files = array_merge(
						$this->getDevelopmentDataFiles($include_dir),
						$files
					);
				}
			}
		} else {
			// Load data files from composer module directories.
			$files = $this->getComposerDataFiles();
		}

		return $files;
	}

	// }}}
	// {{{ protected function getComposerDataFiles()

	protected function getComposerDataFiles()
	{
		$files = array();

		$base_path = '../vendor';
		if (is_dir($base_path)) {
			$base_dir = dir($base_path);
			while (false !== ($vendor_name = $base_dir->read())) {
				if ($vendor_name === '.' ||
					$vendor_name === '..' ||
					$vendor_name === 'bin' ||
					$vendor_name === 'autoload.php') {
					continue;
				}

				$vendor_path = $base_path.DIRECTORY_SEPARATOR.$vendor_name;
				if (is_dir($vendor_path)) {
					$vendor_dir = dir($vendor_path);
					while (false !== ($package_name = $vendor_dir->read())) {
						if ($package_name === '.' || $package_name === '..') {
							continue;
						}

						$finder = new Concentrate_DataProvider_FileFinderDirectory(
							$vendor_path.DIRECTORY_SEPARATOR.
							$package_name.DIRECTORY_SEPARATOR.
							'dependencies'
						);

						$files = array_merge(
							$files,
							$finder->getDataFiles()
						);
					}
				}
			}
		}

		return $files;
	}

	// }}}
	// {{{ protected function getPearDataFiles()

	protected function getPearDataFiles($include_dir)
	{
		$files = array();

		// pear versions
		$include_dir = str_replace('pear/lib', 'pear/data',
			$include_dir);

		if (is_dir($include_dir)) {
			$dir = dir($include_dir);
			while (false !== ($sub_dir = $dir->read())) {

				$dependency_dir = $include_dir.DIRECTORY_SEPARATOR.
					$sub_dir.DIRECTORY_SEPARATOR.'dependencies';

				$finder = new Concentrate_DataProvider_FileFinderDirectory(
					$dependency_dir);

				foreach ($finder->getDataFiles() as $filename) {
					$key = $this->getPearKey($filename);
					if (!isset($files[$key])) {
						$files[$key] = $filename;
					}
				}
			}
			$dir->close();
		}

		return $files;
	}

	// }}}
	// {{{ protected function getDevelopmentDataFiles()

	protected function getDevelopmentDataFiles($include_dir)
	{
		$files = array();

		$dependency_dir = $include_dir.DIRECTORY_SEPARATOR.'dependencies';

		$finder = new Concentrate_DataProvider_FileFinderDirectory(
			$dependency_dir);

		foreach ($finder->getDataFiles() as $filename) {
			$key = $this->getDevelopmentKey($filename);
			if (!isset($files[$key])) {
				$files[$key] = $filename;
			}
		}

		return $files;
	}

	// }}}
	// {{{ protected function getPearKey()

	protected function getPearKey($filename)
	{
		$key = $filename;

		$matches = array();
		$expression = '!data/(.*)?/dependencies/(.*)?$!';
		if (preg_match($expression, $filename, $matches) === 1) {
			$key = strtolower($matches[1]).'/'.$matches[2];
		}

		return $key;
	}

	// }}}
	// {{{ protected function getDevelopmentKey()

	protected function getDevelopmentKey($filename)
	{
		$key = $filename;

		$matches = array();
		$expression = '!packages/(.*)?/.*?/dependencies/(.*)?$!';
		if (preg_match($expression, $filename, $matches) === 1) {
			$key = strtolower($matches[1]).'/'.$matches[2];
		}

		return $key;
	}

	// }}}
	// {{{ protected function getIncludeDirs()

	protected function getIncludeDirs()
	{
		$include_path = get_include_path();

		$dirs   = explode(PATH_SEPARATOR, $include_path);
		$dirs[] = '..';

		return $dirs;
	}

	// }}}
}

?>
