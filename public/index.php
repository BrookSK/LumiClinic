<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Http\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = App::bootstrap();
$request = Request::fromGlobals();

$response = $app->handle($request);
$response->send();
