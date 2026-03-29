<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class AccessToken
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function latest(): ?array
    {
        $statement = $this->pdo->query(
            'SELECT id, client_id, api_key, item_id, created_at, updated_at
             FROM access_token
             WHERE api_key IS NOT NULL
               AND api_key <> ""
             ORDER BY updated_at DESC, id DESC
             LIMIT 1'
        );

        $token = $statement->fetch();

        return $token ?: null;
    }
}
