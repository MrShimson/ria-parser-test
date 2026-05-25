<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\NewsFilters;
use App\Dto\NewsItem;
use App\Dto\NewsPage;
use App\Dto\NewsRow;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class NewsRepository implements NewsRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    public function upsert(NewsItem $item, int $categoryId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO news (url, title, description, category_id, image_url, published_at)
             VALUES (:url, :title, :description, :category_id, :image_url, :published_at)
             ON DUPLICATE KEY UPDATE
                 title        = VALUES(title),
                 description  = VALUES(description),
                 category_id  = VALUES(category_id),
                 image_url    = VALUES(image_url),
                 published_at = VALUES(published_at)'
        );

        $stmt->execute([
            'url'          => $item->url,
            'title'        => $item->title,
            'description'  => $item->description,
            'category_id'  => $categoryId,
            'image_url'    => $item->imageUrl,
            'published_at' => $item->publishedAtUtc->format('Y-m-d H:i:s'),
        ]);
    }

    public function findByFilters(NewsFilters $f): NewsPage
    {
        $where  = ['n.published_at BETWEEN :from AND :to'];
        $params = [
            'from' => $f->fromUtc->format('Y-m-d H:i:s'),
            'to'   => $f->toUtc->format('Y-m-d H:i:s'),
        ];

        if ($f->categoryId !== null) {
            $where[]               = 'n.category_id = :category_id';
            $params['category_id'] = $f->categoryId;
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM news n WHERE {$whereClause}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($f->page - 1) * $f->perPage;
        $params['limit']  = $f->perPage;
        $params['offset'] = $offset;

        $stmt = $this->pdo->prepare(
            "SELECT n.id, n.url, n.title, n.description, c.slug, n.image_url, n.published_at
             FROM news n
             JOIN categories c ON c.id = n.category_id
             WHERE {$whereClause}
             ORDER BY n.published_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue('limit',  $f->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset,     PDO::PARAM_INT);
        foreach ($params as $k => $v) {
            if ($k !== 'limit' && $k !== 'offset') {
                $stmt->bindValue($k, $v);
            }
        }
        $stmt->execute();

        $utc   = new DateTimeZone('UTC');
        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = new NewsRow(
                id:             (int) $row['id'],
                url:            $row['url'],
                title:          $row['title'],
                description:    $row['description'],
                slug:           $row['slug'],
                imageUrl:       $row['image_url'],
                publishedAtUtc: new DateTimeImmutable($row['published_at'], $utc),
            );
        }

        return new NewsPage($items, $total, $f->page, $f->perPage);
    }

    /** @return array<int, string> id => slug */
    public function findActiveCategories(): array
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
