<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Restore Authorization on CGI / cPanel / LiteSpeed
|--------------------------------------------------------------------------
| Alguns hosts removem o header Authorization antes do PHP. Sem isto o
| Sanctum não vê o Bearer e a API responde 401 → o portal faz logout.
*/
foreach ([
    'HTTP_AUTHORIZATION',
    'REDIRECT_HTTP_AUTHORIZATION',
    'REDIRECT_REDIRECT_HTTP_AUTHORIZATION',
] as $authKey) {
    if (! empty($_SERVER[$authKey])) {
        if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER[$authKey];
        }
        break;
    }
}

if (empty($_SERVER['HTTP_AUTHORIZATION']) && ! empty($_SERVER['HTTP_X_KANGURO_AUTHORIZATION'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['HTTP_X_KANGURO_AUTHORIZATION'];
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());

