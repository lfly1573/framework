<?php

define('ROOT_PATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);
$app = require ROOT_PATH . 'vendor/lfly1573/framework/src/init.php';
$http = $app->http;
$response = $http->run();
$response->send();
$http->end($response);
