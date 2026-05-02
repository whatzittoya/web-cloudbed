<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\PaymentController;
use App\Controllers\ReservationController;
use Slim\App;

return static function (App $app): void {
    $app->get('/', [AuthController::class, 'dashboard']);
    $app->get('/login', [AuthController::class, 'showLogin']);
    $app->post('/login', [AuthController::class, 'login']);
    $app->post('/logout', [AuthController::class, 'logout']);
    $app->get('/customers/add', [ReservationController::class, 'addCustomer']);
    $app->get('/payments', [PaymentController::class, 'index']);
    $app->get('/payments/scheduler', [PaymentController::class, 'getScheduler']);
    $app->post('/payments/scheduler', [PaymentController::class, 'updateScheduler']);
    $app->post('/payments/{paymentId}/send', [PaymentController::class, 'send']);
    $app->get('/reservations', [ReservationController::class, 'index']);
    $app->post('/reservations/pull', [ReservationController::class, 'pull']);
    $app->get('/reservations/scheduler', [ReservationController::class, 'getScheduler']);
    $app->post('/reservations/scheduler', [ReservationController::class, 'updateScheduler']);
    $app->post('/reservations/{reservationId}/customers', [ReservationController::class, 'storeCustomer']);
};
