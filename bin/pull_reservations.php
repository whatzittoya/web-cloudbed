<?php

declare(strict_types=1);

use App\Models\AccessToken;
use App\Models\Reservation;
use Dotenv\Dotenv;
use GuzzleHttp\Client;

require dirname(__DIR__) . '/vendor/autoload.php';

$rootPath = dirname(__DIR__);

if (file_exists($rootPath . '/.env')) {
    Dotenv::createImmutable($rootPath)->safeLoad();
}

$container = require $rootPath . '/config/container.php';
$settings = $container->get('settings');

/** @var AccessToken $accessTokens */
$accessTokens = $container->get(AccessToken::class);
/** @var Reservation $reservations */
$reservations = $container->get(Reservation::class);
/** @var Client $client */
$client = $container->get(Client::class);

$accessToken = $accessTokens->latest();

if ($accessToken === null) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . "] Cloudbeds API key was not found in access_token.\n");
    exit(1);
}

try {
    $cloudbeds = $settings['cloudbeds'];
    $items = [];
    $statuses = $cloudbeds['reservation_statuses'] ?? [$cloudbeds['reservation_status']];

    foreach ($statuses as $status) {
        $response = $client->request('GET', rtrim($cloudbeds['base_url'], '/') . '/getReservations', [
            'headers' => [
                'accept' => 'application/json',
                'x-api-key' => $accessToken['api_key'],
            ],
            'query' => [
                'status' => $status,
                'includeAllRooms' => 'true',
            ],
            'timeout' => 30,
        ]);

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $reservations = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        foreach ($reservations as $reservation) {
            $reservationId = (string) ($reservation['reservationID'] ?? '');
            $items[$reservationId !== '' ? $reservationId : uniqid('reservation_', true)] = $reservation;
        }
    }

    $items = array_values($items);
    $synced = $reservations->upsertMany($items, $cloudbeds['checked_out_reservation_status']);
    $latestPulledAt = $reservations->latestPulledAt() ?? date('Y-m-d H:i:s');

    fwrite(STDOUT, sprintf(
        "[%s] Synced %d reservation(s). Latest row update: %s\n",
        date('Y-m-d H:i:s'),
        $synced,
        $latestPulledAt
    ));
} catch (\Throwable $exception) {
    fwrite(STDERR, sprintf(
        "[%s] Failed to pull reservations: %s\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage()
    ));
    exit(1);
}
