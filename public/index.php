<?php

declare(strict_types=1);

use App\Middleware\SessionMiddleware;
use DI\Bridge\Slim\Bridge;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$rootPath = dirname(__DIR__);

if (file_exists($rootPath . '/.env')) {
    Dotenv::createImmutable($rootPath)->safeLoad();
}

$container = require $rootPath . '/config/container.php';
$settings = $container->get('settings');

$app = Bridge::create($container);

if ($settings['app']['base_path'] !== '') {
    $app->setBasePath($settings['app']['base_path']);
}

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add(new SessionMiddleware());
$app->addErrorMiddleware(
    $settings['app']['debug'],
    true,
    true
);

(require $rootPath . '/config/routes.php')($app);

$app->run();
