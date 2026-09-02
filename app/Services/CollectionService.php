<?php

declare(strict_types=1);

final class CollectionService
{
    public static function collections(): array
    {
        return Database::pdo()->query(
            'SELECT c.*,
                (SELECT COUNT(*) FROM books b WHERE b.collection_id = c.id) AS book_count
             FROM collections c ORDER BY c.name'
        )->fetchAll();
    }

    public static function seriesList(): array
    {
        return Database::pdo()->query(
            'SELECT s.*,
                (SELECT COUNT(*) FROM books b WHERE b.series_id = s.id) AS book_count
             FROM series s ORDER BY s.name'
        )->fetchAll();
    }

    public static function findCollection(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.*,
                (SELECT COUNT(*) FROM books b WHERE b.collection_id = c.id) AS book_count
             FROM collections c WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findSeries(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT s.*,
                (SELECT COUNT(*) FROM books b WHERE b.series_id = s.id) AS book_count
             FROM series s WHERE s.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function booksInCollection(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT b.*,
                (SELECT GROUP_CONCAT(a.name ORDER BY ba.sort_order, a.name SEPARATOR ', ')
                 FROM book_authors ba INNER JOIN authors a ON a.id = ba.author_id
                 WHERE ba.book_id = b.id) AS author_names
             FROM books b WHERE b.collection_id = ?
             ORDER BY b.volume, b.title"
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public static function booksInSeries(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT b.*,
                (SELECT GROUP_CONCAT(a.name ORDER BY ba.sort_order, a.name SEPARATOR ', ')
                 FROM book_authors ba INNER JOIN authors a ON a.id = ba.author_id
                 WHERE ba.book_id = b.id) AS author_names
             FROM books b WHERE b.series_id = ?
             ORDER BY b.series_number, b.volume, b.title"
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public static function saveCollection(array $data, ?int $id = null): int
    {
        $pdo = Database::pdo();
        if ($id) {
            $pdo->prepare('UPDATE collections SET name=?, description=?, expected_volumes=? WHERE id=?')
                ->execute([
                    trim((string) $data['name']),
                    null_if_blank($data['description'] ?? null),
                    int_or_null($data['expected_volumes'] ?? null),
                    $id,
                ]);
            return $id;
        }
        $pdo->prepare('INSERT INTO collections (name, description, expected_volumes) VALUES (?, ?, ?)')
            ->execute([
                trim((string) $data['name']),
                null_if_blank($data['description'] ?? null),
                int_or_null($data['expected_volumes'] ?? null),
            ]);
        return (int) $pdo->lastInsertId();
    }

    public static function saveSeries(array $data, ?int $id = null): int
    {
        $pdo = Database::pdo();
        if ($id) {
            $pdo->prepare('UPDATE series SET name=?, description=?, expected_volumes=? WHERE id=?')
                ->execute([
                    trim((string) $data['name']),
                    null_if_blank($data['description'] ?? null),
                    int_or_null($data['expected_volumes'] ?? null),
                    $id,
                ]);
            return $id;
        }
        $pdo->prepare('INSERT INTO series (name, description, expected_volumes) VALUES (?, ?, ?)')
            ->execute([
                trim((string) $data['name']),
                null_if_blank($data['description'] ?? null),
                int_or_null($data['expected_volumes'] ?? null),
            ]);
        return (int) $pdo->lastInsertId();
    }

    public static function deleteCollection(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM collections WHERE id = ?')->execute([$id]);
    }

    public static function deleteSeries(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM series WHERE id = ?')->execute([$id]);
    }
}
