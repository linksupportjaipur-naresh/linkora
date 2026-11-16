<?php

/*
| Linkora — front controller for shared hosting (cPanel / Hostinger hPanel).
|
| Layout on the server:
|   /home/<user>/linkora/        ← the app (everything except public/)
|   /home/<user>/public_html/    ← the contents of public/, with THIS file as index.php
|
| If your app folder has another name or place, change $app below.
| Also set PUBLIC_PATH=/home/<user>/public_html in linkora/.env.
*/

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appDir = __DIR__ . '/../linkora';

if (file_exists($maintenance = $appDir . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appDir . '/vendor/autoload.php';

$app = require_once $appDir . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
