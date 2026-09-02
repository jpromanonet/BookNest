<?php

declare(strict_types=1);

final class AuthorService
{
    public static function findOrCreate(string $name): int
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Author name required');
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM authors WHERE name = ?');
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        $pdo->prepare('INSERT INTO authors (name) VALUES (?)')->execute([$name]);
        return (int) $pdo->lastInsertId();
    }

    public static function all(): array
    {
        return Database::pdo()->query(
            'SELECT a.*,
                (SELECT COUNT(*) FROM book_authors ba WHERE ba.author_id = a.id) AS book_count
             FROM authors a
             ORDER BY a.name'
        )->fetchAll();
    }

    public static function paginate(int $page = 1, int $perPage = 20, string $q = ''): array
    {
        $pdo = Database::pdo();
        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $where = '1=1';
        $params = [];
        $q = trim($q);
        if ($q !== '') {
            $where = '(a.name LIKE ? OR a.nationality LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like];
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM authors a WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            "SELECT a.*,
                (SELECT COUNT(*) FROM book_authors ba WHERE ba.author_id = a.id) AS book_count
             FROM authors a
             WHERE {$where}
             ORDER BY a.name
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
            'q' => $q,
        ];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT a.*,
                (SELECT COUNT(*) FROM book_authors ba WHERE ba.author_id = a.id) AS book_count
             FROM authors a WHERE a.id = ?'
        );
        $stmt->execute([$id]);
        $author = $stmt->fetch();
        return $author ?: null;
    }

    public static function books(int $authorId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT b.*,
                (SELECT GROUP_CONCAT(a2.name ORDER BY ba2.sort_order, a2.name SEPARATOR ', ')
                 FROM book_authors ba2 INNER JOIN authors a2 ON a2.id = ba2.author_id
                 WHERE ba2.book_id = b.id) AS author_names
             FROM books b
             INNER JOIN book_authors ba ON ba.book_id = b.id
             WHERE ba.author_id = ?
             ORDER BY b.title"
        );
        $stmt->execute([$authorId]);
        return $stmt->fetchAll();
    }

    public static function stats(int $authorId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN b.reading_status = 'read' THEN 1 ELSE 0 END) AS read_count,
                SUM(CASE WHEN b.reading_status = 'unread' THEN 1 ELSE 0 END) AS unread_count,
                SUM(CASE WHEN b.reading_status = 'reading' THEN 1 ELSE 0 END) AS reading_count
             FROM books b
             INNER JOIN book_authors ba ON ba.book_id = b.id
             WHERE ba.author_id = ?"
        );
        $stmt->execute([$authorId]);
        return $stmt->fetch() ?: [];
    }

    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO authors (name, biography, nationality, birth_date, death_date, notes)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            trim((string) $data['name']),
            null_if_blank($data['biography'] ?? null),
            null_if_blank($data['nationality'] ?? null),
            null_if_blank($data['birth_date'] ?? null),
            null_if_blank($data['death_date'] ?? null),
            null_if_blank($data['notes'] ?? null),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE authors SET name=?, biography=?, nationality=?, birth_date=?, death_date=?, notes=? WHERE id=?'
        );
        $stmt->execute([
            trim((string) $data['name']),
            null_if_blank($data['biography'] ?? null),
            null_if_blank($data['nationality'] ?? null),
            null_if_blank($data['birth_date'] ?? null),
            null_if_blank($data['death_date'] ?? null),
            null_if_blank($data['notes'] ?? null),
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM authors WHERE id = ?')->execute([$id]);
    }
}
