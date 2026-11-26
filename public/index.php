<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Restore Authorization on CGI / cPanel / LiteSpeed / Apache
|--------------------------------------------------------------------------
| Muitos hosts removem Authorization antes do PHP → Sanctum 401.
| As apps também enviam X-Kanguro-Authorization como fallback.
*/
$resolveAuthHeader = static function (): ?string {
    foreach ([
        'HTTP_AUTHORIZATION',
        'REDIRECT_HTTP_AUTHORIZATION',
        'REDIRECT_REDIRECT_HTTP_AUTHORIZATION',
        'HTTP_X_KANGURO_AUTHORIZATION',
        'REDIRECT_HTTP_X_KANGURO_AUTHORIZATION',
    ] as $authKey) {
        if (! empty($_SERVER[$authKey])) {
            return (string) $_SERVER[$authKey];
        }
    }

    if (function_exists('getallheaders')) {
        foreach (getallheaders() ?: [] as $key => $value) {
            if (strcasecmp((string) $key, 'Authorization') === 0
                || strcasecmp((string) $key, 'X-Kanguro-Authorization') === 0) {
                return (string) $value;
            }
        }
    }

    if (function_exists('apache_request_headers')) {
        foreach (apache_request_headers() ?: [] as $key => $value) {
            if (strcasecmp((string) $key, 'Authorization') === 0
                || strcasecmp((string) $key, 'X-Kanguro-Authorization') === 0) {
                return (string) $value;
            }
        }
    }

    return null;
};

$authHeader = $resolveAuthHeader();
if ($authHeader) {
    $_SERVER['HTTP_AUTHORIZATION'] = $authHeader;
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

