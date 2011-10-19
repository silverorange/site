<?php

require_once 'PEAR/PackageFileManager2.php';

$version = '1.5.39';
$notes = <<<EOT
No release notes for you!
EOT;

$description =<<<EOT
Framework for building a website.

* An OO-style API
* Uses Swat
* A set of user-interface widgets
EOT;

$package = new PEAR_PackageFileManager2();
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$result = $package->setOptions(
	array(
		'filelistgenerator' => 'svn',
		'simpleoutput'      => true,
		'baseinstalldir'    => '/',
		'packagedirectory'  => './',
		'dir_roles'         => array(
			'Site'          => 'php',
			'locale'        => 'data',
			'www'           => 'data',
			'dependencies'  => 'data',
			'/'             => 'data',
		),
	)
);

$package->setPackage('Site');
$package->setSummary('Framework for building a website');
$package->setDescription($description);
$package->setChannel('pear.silverorange.com');
$package->setPackageType('php');
$package->setLicense('LGPL', 'http://www.gnu.org/copyleft/lesser.html');

$package->setReleaseVersion($version);
$package->setReleaseStability('stable');
$package->setAPIVersion('0.0.1');
$package->setAPIStability('stable');
$package->setNotes($notes);

$package->addIgnore('package.php');

$package->addMaintainer('lead', 'nrf', 'Nathan Fredrickson', 'nathan@silverorange.com');
$package->addMaintainer('lead', 'gauthierm', 'Mike Gauthier', 'mike@silverorange.com');

$package->addReplacement('Site/Site.php', 'pear-config', '@DATA-DIR@', 'data_dir');

$package->setPhpDep('5.1.5');
$package->setPearinstallerDep('1.4.0');
$package->addPackageDepWithChannel('required', 'Concentrate', 'pear.silverorange.com', '0.0.1');
$package->addPackageDepWithChannel('required', 'Swat', 'pear.silverorange.com', '1.4.57');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.2.2');
$package->addPackageDepWithChannel('required', 'Mail', 'pear.php.net', '1.1.10');
$package->addPackageDepWithChannel('required', 'Mail_Mime', 'pear.silverorange.com', '1.5.2so3');
$package->addPackageDepWithChannel('required', 'Net_SMTP', 'pear.php.net', '1.2.8');
$package->addPackageDepWithChannel('optional', 'XML_RPC2', 'pear.silverorange.com', '1.0.3so5');
$package->addPackageDepWithChannel('required', 'Text_Password', 'pear.php.net', '1.1.1');
$package->addPackageDepWithChannel('optional', 'Services_Akismet2', 'pear.php.net', '0.2.0');
$package->addPackageDepWithChannel('optional', 'Services_Amazon_S3', 'pear.php.net', '0.3.1');
$package->addExtensionDep('optional', 'imagick', '2.0.0');
$package->addExtensionDep('optional', 'uploadprogress', '0.3.0');
$package->addExtensionDep('optional', 'memcached', '0.1.5');
$package->generateContents();

if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
	$package->writePackageFile();
} else {
	$package->debugPackageFile();
}

?>
