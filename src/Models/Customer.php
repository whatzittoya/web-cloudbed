<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Customer
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function upsertFromReservation(array $reservation): void
    {
        $reservationId = (string) ($reservation['reservation_id'] ?? '');
        $guestId = (string) ($reservation['guest_id'] ?? '');
        $existingId = $this->findExistingIdByGuestId($guestId, $reservationId)
            ?? $this->findExistingIdByReservationId($reservationId);

        $payload = [
            'code' => 'cloudbed_guest',
            'name' => (string) ($reservation['guest_name'] ?? ''),
            'notes' => (string) ($reservation['guest_id'] ?? ''),
            'address' => $reservation['room_type_name'] !== null && $reservation['room_type_name'] !== ''
                ? (string) $reservation['room_type_name']
                : null,
            'postcode' => $reservation['property_id'] !== null && $reservation['property_id'] !== ''
                ? (string) $reservation['property_id']
                : null,
            'suburb' => $reservation['room_name'] !== null && $reservation['room_name'] !== ''
                ? (string) $reservation['room_name']
                : null,
            'reservation_id' => $reservationId,
            'created' => (string) ($reservation['start_date'] ?? ''),
            'expired' => (string) ($reservation['end_date'] ?? ''),
        ];

        if ($existingId !== null) {
            $statement = $this->pdo->prepare(
                'UPDATE tbl_customers
                 SET active = b\'1\',
                     code = :code,
                     name = :name,
                     notes = :notes,
                     address = :address,
                     postcode = :postcode,
                     suburb = :suburb,
                     reservation_id = :reservation_id,
                     created = :created,
                     expired = :expired
                 WHERE id = :id'
            );

            $statement->execute($payload + ['id' => $existingId]);

            return;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO tbl_customers (
                active,
                code,
                name,
                notes,
                address,
                postcode,
                suburb,
                reservation_id,
                created,
                expired
            ) VALUES (
                b\'1\',
                :code,
                :name,
                :notes,
                :address,
                :postcode,
                :suburb,
                :reservation_id,
                :created,
                :expired
            )'
        );

        $statement->execute($payload);
    }

    private function findExistingIdByReservationId(string $reservationId): ?int
    {
        if ($reservationId === '') {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT id
             FROM tbl_customers
             WHERE reservation_id = :reservation_id
             ORDER BY id ASC
             LIMIT 1'
        );

        $statement->execute([
            'reservation_id' => $reservationId,
        ]);

        $row = $statement->fetch();

        return $row !== false ? (int) $row['id'] : null;
    }

    private function findExistingIdByGuestId(string $guestId, string $reservationId): ?int
    {
        if ($guestId === '') {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT id
             FROM tbl_customers
             WHERE notes = :guest_id
             ORDER BY
                 CASE
                     WHEN reservation_id = :reservation_id THEN 0
                     WHEN reservation_id IS NULL OR reservation_id = \'\' OR reservation_id = \'null\' THEN 1
                     ELSE 2
                 END,
                 id DESC
             LIMIT 1'
        );

        $statement->execute([
            'guest_id' => $guestId,
            'reservation_id' => $reservationId,
        ]);

        $row = $statement->fetch();

        return $row !== false ? (int) $row['id'] : null;
    }
}
