<?php

define('ROOT_PATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);
require ROOT_PATH . 'vendor/autoload.php';
$app = new \lfly\App();
$http = $app->http;
$response = $http->run();
$response->send();
$http->end($response);
