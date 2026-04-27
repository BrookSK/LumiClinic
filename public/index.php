<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

use App\Core\App;
use App\Core\Http\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = App::bootstrap();
$request = Request::fromGlobals();

$response = $app->handle($request);
$response->send();
