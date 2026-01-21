<?php

declare(strict_types=1);

use App\Controllers\Auth\LoginController;
use App\Controllers\Clinics\ClinicController;
use App\Controllers\DashboardController;
use App\Controllers\Settings\SettingsController;
use App\Controllers\Users\UserController;

$router->get('/', [DashboardController::class, 'index']);

$router->get('/login', [LoginController::class, 'show']);
$router->post('/login', [LoginController::class, 'login']);
$router->post('/logout', [LoginController::class, 'logout']);

$router->get('/clinic', [ClinicController::class, 'edit']);
$router->post('/clinic', [ClinicController::class, 'update']);

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/create', [UserController::class, 'create']);
$router->post('/users/create', [UserController::class, 'store']);

$router->get('/settings', [SettingsController::class, 'index']);
$router->post('/settings', [SettingsController::class, 'update']);

$router->get('/settings/terminology', [SettingsController::class, 'terminology']);
$router->post('/settings/terminology', [SettingsController::class, 'updateTerminology']);
