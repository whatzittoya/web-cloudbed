<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Reservation
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function customerCandidates(string $search = ''): array
    {
        $sql = 'SELECT
                    r.reservation_id,
                    r.guest_name,
                    r.status,
                    r.start_date,
                    r.end_date,
                    r.source_name,
                    r.room_type_name,
                    r.room_name,
                    CASE
                        WHEN EXISTS (
                            SELECT 1
                            FROM tbl_customers c
                            WHERE c.reservation_id = r.reservation_id
                              AND CAST(c.active AS UNSIGNED) = 1
                        ) THEN 1
                        ELSE 0
                    END AS customer_added
                FROM tbl_reservation_cloudbed r';
        $parameters = [];

        if ($search !== '') {
            $sql .= ' WHERE r.guest_name LIKE :search';
            $parameters['search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY r.date_modified DESC, r.start_date DESC LIMIT 100';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findByReservationId(string $reservationId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                reservation_id,
                guest_name,
                status,
                start_date,
                end_date,
                adults,
                children,
                balance,
                source_id,
                source_name,
                room_type_name,
                room_name,
                guest_id,
                profile_id,
                property_id,
                date_created,
                date_modified,
                third_party_identifier,
                allotment_block_code,
                group_code,
                origin
             FROM tbl_reservation_cloudbed
             WHERE reservation_id = :reservation_id
             LIMIT 1'
        );

        $statement->execute([
            'reservation_id' => $reservationId,
        ]);

        $detail = $statement->fetch();

        return $detail ?: null;
    }

    public function allCritical(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                r.reservation_id,
                r.guest_name,
                r.status,
                r.start_date,
                r.end_date,
                r.room_type_name,
                r.room_name,
                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM tbl_customers c
                        WHERE c.reservation_id = r.reservation_id
                          AND CAST(c.active AS UNSIGNED) = 1
                    ) THEN 1
                    ELSE 0
                END AS customer_added
             FROM tbl_reservation_cloudbed r
             ORDER BY start_date DESC, date_modified DESC'
        );

        return $statement->fetchAll();
    }

    public function upsertMany(array $reservations): int
    {
        if ($reservations === []) {
            return 0;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO tbl_reservation_cloudbed (
                property_id,
                reservation_id,
                date_created,
                date_modified,
                status,
                guest_id,
                profile_id,
                guest_name,
                start_date,
                end_date,
                adults,
                children,
                balance,
                source_id,
                source_name,
                room_type_name,
                room_name,
                third_party_identifier,
                allotment_block_code,
                group_code,
                origin
            ) VALUES (
                :property_id,
                :reservation_id,
                :date_created,
                :date_modified,
                :status,
                :guest_id,
                :profile_id,
                :guest_name,
                :start_date,
                :end_date,
                :adults,
                :children,
                :balance,
                :source_id,
                :source_name,
                :room_type_name,
                :room_name,
                :third_party_identifier,
                :allotment_block_code,
                :group_code,
                :origin
            )
            ON DUPLICATE KEY UPDATE
                property_id = VALUES(property_id),
                date_created = VALUES(date_created),
                date_modified = VALUES(date_modified),
                status = VALUES(status),
                guest_id = VALUES(guest_id),
                profile_id = VALUES(profile_id),
                guest_name = VALUES(guest_name),
                start_date = VALUES(start_date),
                end_date = VALUES(end_date),
                adults = VALUES(adults),
                children = VALUES(children),
                balance = VALUES(balance),
                source_id = VALUES(source_id),
                source_name = VALUES(source_name),
                room_type_name = VALUES(room_type_name),
                room_name = VALUES(room_name),
                third_party_identifier = VALUES(third_party_identifier),
                allotment_block_code = VALUES(allotment_block_code),
                group_code = VALUES(group_code),
                origin = VALUES(origin)'
        );

        $this->pdo->beginTransaction();

        try {
            foreach ($reservations as $reservation) {
                $firstRoom = isset($reservation['rooms'][0]) && is_array($reservation['rooms'][0])
                    ? $reservation['rooms'][0]
                    : [];

                $statement->execute([
                    'property_id' => (string) ($reservation['propertyID'] ?? ''),
                    'reservation_id' => (string) ($reservation['reservationID'] ?? ''),
                    'date_created' => (string) ($reservation['dateCreated'] ?? ''),
                    'date_modified' => (string) ($reservation['dateModified'] ?? ''),
                    'status' => (string) ($reservation['status'] ?? ''),
                    'guest_id' => (string) ($reservation['guestID'] ?? ''),
                    'profile_id' => (string) ($reservation['profileID'] ?? ''),
                    'guest_name' => (string) ($reservation['guestName'] ?? ''),
                    'start_date' => (string) ($reservation['startDate'] ?? ''),
                    'end_date' => (string) ($reservation['endDate'] ?? ''),
                    'adults' => (int) ($reservation['adults'] ?? 0),
                    'children' => (int) ($reservation['children'] ?? 0),
                    'balance' => (int) ($reservation['balance'] ?? 0),
                    'source_id' => (string) ($reservation['sourceID'] ?? ''),
                    'source_name' => (string) ($reservation['sourceName'] ?? ''),
                    'room_type_name' => ($firstRoom['roomTypeName'] ?? '') !== '' ? (string) $firstRoom['roomTypeName'] : null,
                    'room_name' => ($firstRoom['roomName'] ?? '') !== '' ? (string) $firstRoom['roomName'] : null,
                    'third_party_identifier' => $reservation['thirdPartyIdentifier'] ?: null,
                    'allotment_block_code' => $reservation['allotmentBlockCode'] ?: null,
                    'group_code' => $reservation['groupCode'] ?: null,
                    'origin' => $reservation['origin'] !== '' ? $reservation['origin'] : null,
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }

        return count($reservations);
    }
}
