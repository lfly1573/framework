<?php

$app = require '../lfly/init.php';
$http = $app->http;
$response = $http->run();
$response->send();
$http->end($response);