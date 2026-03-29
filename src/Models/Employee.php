<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Employee
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findActiveByCredentials(string $name, string $pin): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, code, jobTitle, email, phone1
             FROM tbl_employees
             WHERE name = :name
               AND pin = :pin
               AND CAST(active AS UNSIGNED) = 1
             LIMIT 1'
        );

        $statement->execute([
            'name' => $name,
            'pin' => $pin,
        ]);

        $employee = $statement->fetch();

        return $employee ?: null;
    }
}
