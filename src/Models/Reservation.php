<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Reservation
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function latestPulledAt(): ?string
    {
        $statement = $this->pdo->query(
            'SELECT MAX(updated_at) AS latest_pulled_at
             FROM tbl_reservation_cloudbed'
        );

        $row = $statement->fetch();
        $value = $row['latest_pulled_at'] ?? null;

        return $value !== null && $value !== '' ? (string) $value : null;
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
                FROM tbl_reservation_cloudbed r
                WHERE r.status = :status';
        $parameters = [
            'status' => 'checked_in',
        ];

        if ($search !== '') {
            $sql .= ' AND r.guest_name LIKE :search';
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
             WHERE r.status = \'checked_in\'
             ORDER BY start_date DESC, date_modified DESC'
        );

        return $statement->fetchAll();
    }

    public function upsertMany(array $reservations, string $checkedOutStatus = 'checked_out'): int
    {
        if ($reservations === []) {
            return 0;
        }

        $reservationIds = array_values(array_filter(
            array_map(fn ($r) => (string) ($r['reservationID'] ?? ''), $reservations),
            fn ($id) => $id !== '',
        ));

        if ($reservationIds === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($reservationIds), '?'));
        $deleteStmt = $this->pdo->prepare(
            "DELETE FROM tbl_reservation_cloudbed WHERE reservation_id IN ($placeholders)"
        );

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
            )'
        );

        $this->pdo->beginTransaction();

        try {
            $deleteStmt->execute($reservationIds);

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
                    'origin' => ($reservation['origin'] ?? '') !== '' ? $reservation['origin'] : null,
                ]);
            }

            $stalePlaceholders = implode(',', array_fill(0, count($reservationIds), '?'));
            $this->pdo->prepare(
                "DELETE FROM tbl_reservation_cloudbed WHERE reservation_id NOT IN ($stalePlaceholders)"
            )->execute($reservationIds);

            $this->syncCustomerActiveStatuses($checkedOutStatus);

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }

        return count($reservations);
    }

    private function syncCustomerActiveStatuses(string $checkedOutStatus): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE tbl_customers c
             INNER JOIN tbl_reservation_cloudbed r ON r.reservation_id = c.reservation_id
             SET c.active = CASE
                 WHEN r.status = :checked_out_status THEN b\'0\'
                 ELSE b\'1\'
             END
             WHERE c.reservation_id IS NOT NULL
               AND c.reservation_id <> \'\''
        );

        $statement->execute([
            'checked_out_status' => $checkedOutStatus,
        ]);
    }
}
