#!/usr/bin/php
<?php

$package_dir = __DIR__.DIRECTORY_SEPARATOR.
	'..'.DIRECTORY_SEPARATOR.
	'..'.DIRECTORY_SEPARATOR;

set_include_path(
	$package_dir.PATH_SEPARATOR.
	'/my/pear/dir/'
);

ini_set('memory_limit', -1);
set_time_limit(30000);
proc_nice(19);

require_once __DIR__.'/AMQPTestServer.php';

$parser = SiteAMQPCommandLine::fromXMLFile(__DIR__.'/amqp-test-server.xml');
$logger = new SiteCommandLineLogger($parser);
$config = __DIR__.'/amqp-test.ini';
$app = new AMQPTestServer(
	'site-test.strrev',
	$parser,
	$logger,
	$config
);
$app();

?>
