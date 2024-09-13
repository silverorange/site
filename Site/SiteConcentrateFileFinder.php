<?php

/**
 * @package   Site
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteConcentrateFileFinder
	implements Concentrate_DataProvider_FileFinderInterface
{


	public function getDataFiles()
	{
		// Load data files from composer module directories and from site
		// dependency directory.
		return array_merge(
			$this->getSiteDataFiles(),
			$this->getComposerDataFiles()
		);
	}




	protected function getWwwPath()
	{
		$www_path = realpath('.');

		while (basename($www_path) !== 'www') {
			$www_path = dirname($www_path);
		}

		return $www_path;
	}




	protected function getRootPath()
	{
		return dirname($this->getWwwPath());
	}




	protected function getComposerDataFiles()
	{
		$files = [];

		$www_path = $this->getWwwPath();
		$base_path = dirname($www_path).DIRECTORY_SEPARATOR.'vendor';
		if (is_dir($base_path)) {
			$base_dir = dir($base_path);
			$vendor_name = $base_dir->read();
			while ($vendor_name !== false) {
				if ($vendor_name === '.' ||
					$vendor_name === '..' ||
					$vendor_name === 'bin' ||
					$vendor_name === 'autoload.php') {
					$vendor_name = $base_dir->read();
					continue;
				}

				$vendor_path = $base_path.DIRECTORY_SEPARATOR.$vendor_name;
				if (is_dir($vendor_path)) {
					$vendor_dir = dir($vendor_path);
					$package_name = $vendor_dir->read();
					while ($package_name !== false) {
						if ($package_name === '.' || $package_name === '..') {
							$package_name = $vendor_dir->read();
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
						$package_name = $vendor_dir->read();
					}
				}
				$vendor_name = $base_dir->read();
			}
		}

		return $files;
	}




	protected function getSiteDataFiles()
	{
		$files = [];

		$finder = new Concentrate_DataProvider_FileFinderDirectory(
			$this->getRootPath().DIRECTORY_SEPARATOR.'dependencies'
		);

		foreach ($finder->getDataFiles() as $filename) {
			$files[] = $filename;
		}

		return $files;
	}




	protected function getDevelopmentDataFiles($include_dir)
	{
		$files = [];

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




	protected function getDevelopmentKey($filename)
	{
		$key = $filename;

		$matches = [];
		$expression = '!packages/(.*)?/.*?/dependencies/(.*)?$!';
		if (preg_match($expression, $filename, $matches) === 1) {
			$key = mb_strtolower($matches[1]).'/'.$matches[2];
		}

		return $key;
	}




	protected function getIncludeDirs()
	{
		$include_path = get_include_path();

		$dirs   = explode(PATH_SEPARATOR, $include_path);
		$dirs[] = '..';

		return $dirs;
	}


}

?>
