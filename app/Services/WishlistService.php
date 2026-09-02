<?php

declare(strict_types=1);

final class WishlistService
{
    public static function all(): array
    {
        return Database::pdo()->query('SELECT * FROM wishlist ORDER BY priority ASC, title ASC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM wishlist WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        Database::pdo()->prepare(
            'INSERT INTO wishlist (title, author, isbn, desired_edition, publisher, priority, found_price, store, url, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            trim((string) $data['title']),
            null_if_blank($data['author'] ?? null),
            null_if_blank($data['isbn'] ?? null),
            null_if_blank($data['desired_edition'] ?? null),
            null_if_blank($data['publisher'] ?? null),
            int_or_null($data['priority'] ?? 3) ?? 3,
            float_or_null($data['found_price'] ?? null),
            null_if_blank($data['store'] ?? null),
            null_if_blank($data['url'] ?? null),
            null_if_blank($data['notes'] ?? null),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::pdo()->prepare(
            'UPDATE wishlist SET title=?, author=?, isbn=?, desired_edition=?, publisher=?, priority=?, found_price=?, store=?, url=?, notes=? WHERE id=?'
        )->execute([
            trim((string) $data['title']),
            null_if_blank($data['author'] ?? null),
            null_if_blank($data['isbn'] ?? null),
            null_if_blank($data['desired_edition'] ?? null),
            null_if_blank($data['publisher'] ?? null),
            int_or_null($data['priority'] ?? 3) ?? 3,
            float_or_null($data['found_price'] ?? null),
            null_if_blank($data['store'] ?? null),
            null_if_blank($data['url'] ?? null),
            null_if_blank($data['notes'] ?? null),
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM wishlist WHERE id = ?')->execute([$id]);
    }

    public static function moveToLibrary(int $id): int
    {
        $item = self::find($id);
        if (!$item) {
            throw new RuntimeException('Wishlist item not found');
        }
        $isbn = (string) ($item['isbn'] ?? '');
        $isbn10 = strlen(preg_replace('/\D/', '', $isbn)) === 10 ? $isbn : null;
        $isbn13 = strlen(preg_replace('/\D/', '', $isbn)) === 13 ? $isbn : null;

        $bookId = BookService::create([
            'title' => $item['title'],
            'authors_text' => $item['author'] ?? '',
            'isbn10' => $isbn10,
            'isbn13' => $isbn13,
            'publisher_name' => $item['publisher'] ?? null,
            'edition' => $item['desired_edition'] ?? null,
            'purchase_price' => $item['found_price'] ?? null,
            'purchase_place' => $item['store'] ?? null,
            'notes' => $item['notes'] ?? null,
            'reading_status' => 'unread',
            'physical_condition' => 'good',
        ]);
        self::delete($id);
        return $bookId;
    }
}
