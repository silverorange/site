<?php

/**
 * @copyright 2010-2024 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteConcentrateFileFinder implements Concentrate_DataProvider_FileFinderInterface
{
    public function getDataFiles()
    {
        // Load data files from composer module directories and from site
        // dependency directory.
        return [
            ...$this->getSiteDataFiles(),
            ...$this->getComposerDataFiles(),
        ];
    }

    protected function getRootPath(): string
    {
        return dirname(__DIR__, 4);
    }

    /**
     * @return string[]
     */
    protected function getComposerDataFiles(): array
    {
        $files = [];

        $base_path = $this->getRootPath() . DIRECTORY_SEPARATOR . 'vendor';
        if (is_dir($base_path)) {
            $base_dir = dir($base_path);
            $vendor_name = $base_dir->read();
            while ($vendor_name !== false) {
                if ($vendor_name === '.'
                    || $vendor_name === '..'
                    || $vendor_name === 'bin'
                    || $vendor_name === 'autoload.php') {
                    $vendor_name = $base_dir->read();

                    continue;
                }

                $vendor_path = $base_path . DIRECTORY_SEPARATOR . $vendor_name;
                if (is_dir($vendor_path)) {
                    $vendor_dir = dir($vendor_path);
                    $package_name = $vendor_dir->read();
                    while ($package_name !== false) {
                        if ($package_name === '.' || $package_name === '..') {
                            $package_name = $vendor_dir->read();

                            continue;
                        }

                        $finder = new Concentrate_DataProvider_FileFinderDirectory(
                            $vendor_path . DIRECTORY_SEPARATOR .
                            $package_name . DIRECTORY_SEPARATOR .
                            'dependencies'
                        );

                        $files = [
                            ...$files,
                            ...$finder->getDataFiles(),
                        ];
                        $package_name = $vendor_dir->read();
                    }
                }
                $vendor_name = $base_dir->read();
            }
        }

        return $files;
    }

    /**
     * @return string[]
     */
    protected function getSiteDataFiles(): array
    {
        $files = [];

        $finder = new Concentrate_DataProvider_FileFinderDirectory(
            $this->getRootPath() . DIRECTORY_SEPARATOR . 'dependencies'
        );

        foreach ($finder->getDataFiles() as $filename) {
            $files[] = $filename;
        }

        return $files;
    }
}
