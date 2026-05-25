<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class CategoryRepository
{
    public function __construct(private readonly PDO $pdo) {}

    /** @return array<string, int> slug => id */
    public function loadMap(): array
    {
        $rows = $this->pdo
            ->query('SELECT id, slug FROM categories')
            ->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['slug']] = (int) $row['id'];
        }
        return $map;
    }

    public function upsert(string $slug): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categories (slug) VALUES (?)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $stmt->execute([$slug]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int, string> id => slug */
    public function findActiveWithNews(): array
    {
        $rows = $this->pdo
            ->query(
                'SELECT c.id, c.slug FROM categories c
                 WHERE EXISTS (SELECT 1 FROM news n WHERE n.category_id = c.id)
                 ORDER BY c.slug'
            )
            ->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['id']] = $row['slug'];
        }
        return $result;
    }
}
