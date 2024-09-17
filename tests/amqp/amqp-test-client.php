#!/usr/bin/php
<?php

$package_dir = __DIR__ . DIRECTORY_SEPARATOR .
    '..' . DIRECTORY_SEPARATOR .
    '..' . DIRECTORY_SEPARATOR;

set_include_path(
    $package_dir . PATH_SEPARATOR .
    '/my/pear/dir/'
);

ini_set('memory_limit', -1);
set_time_limit(30000);
proc_nice(19);

require_once __DIR__ . '/AMQPTestClient.php';

$app = new AMQPTestClient(
    'amqp_test_client',
    __DIR__ . '/amqp-test.ini',
    'AMQP Test Client',
    'Tests SiteAMQPModule and SiteAMQPApplication sync and async methods.'
);

$app->run();
