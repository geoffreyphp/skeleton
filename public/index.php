<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../.geoffrey/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

$app = \Geoffrey\Bootstrap::create(dirname(__DIR__));

$app->handleRequest(Request::capture());