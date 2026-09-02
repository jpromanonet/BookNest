<?php

declare(strict_types=1);

final class BookService
{
    public static function countAll(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM books')->fetchColumn();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT b.*, p.name AS publisher_name, c.name AS collection_name, s.name AS series_name
            FROM books b
            LEFT JOIN publishers p ON p.id = b.publisher_id
            LEFT JOIN collections c ON c.id = b.collection_id
            LEFT JOIN series s ON s.id = b.series_id
            WHERE b.id = ?');
        $stmt->execute([$id]);
        $book = $stmt->fetch();
        if (!$book) {
            return null;
        }
        $book['authors'] = self::authorsFor($id);
        $book['genres'] = self::genresFor($id);
        $book['tags'] = self::tagsFor($id);
        $book['authors_text'] = implode(', ', array_column($book['authors'], 'name'));
        $book['genres_text'] = implode(', ', array_column($book['genres'], 'name'));
        $book['tags_text'] = implode(', ', array_column($book['tags'], 'name'));
        return $book;
    }

    public static function authorsFor(int $bookId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT a.* FROM authors a
             INNER JOIN book_authors ba ON ba.author_id = a.id
             WHERE ba.book_id = ?
             ORDER BY ba.sort_order, a.name'
        );
        $stmt->execute([$bookId]);
        return $stmt->fetchAll();
    }

    public static function genresFor(int $bookId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT g.* FROM genres g
             INNER JOIN book_genres bg ON bg.genre_id = g.id
             WHERE bg.book_id = ?
             ORDER BY g.name'
        );
        $stmt->execute([$bookId]);
        return $stmt->fetchAll();
    }

    public static function tagsFor(int $bookId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT t.* FROM tags t
             INNER JOIN book_tags bt ON bt.tag_id = t.id
             WHERE bt.book_id = ?
             ORDER BY t.name'
        );
        $stmt->execute([$bookId]);
        return $stmt->fetchAll();
    }

    public static function search(array $filters = [], string $sort = 'title', string $dir = 'asc', int $page = 1, int $perPage = 25): array
    {
        $where = ['1=1'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(b.title LIKE ? OR b.subtitle LIKE ? OR b.isbn10 LIKE ? OR b.isbn13 LIKE ?
                OR p.name LIKE ? OR c.name LIKE ? OR s.name LIKE ?
                OR EXISTS (
                    SELECT 1 FROM book_authors ba2
                    INNER JOIN authors a2 ON a2.id = ba2.author_id
                    WHERE ba2.book_id = b.id AND a2.name LIKE ?
                )
                OR EXISTS (
                    SELECT 1 FROM book_genres bg2
                    INNER JOIN genres g2 ON g2.id = bg2.genre_id
                    WHERE bg2.book_id = b.id AND g2.name LIKE ?
                )
                OR EXISTS (
                    SELECT 1 FROM book_tags bt2
                    INNER JOIN tags t2 ON t2.id = bt2.tag_id
                    WHERE bt2.book_id = b.id AND t2.name LIKE ?
                ))';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like);
        }

        if (!empty($filters['author_id'])) {
            $where[] = 'EXISTS (SELECT 1 FROM book_authors ba3 WHERE ba3.book_id = b.id AND ba3.author_id = ?)';
            $params[] = (int) $filters['author_id'];
        }
        if (!empty($filters['publisher_id'])) {
            $where[] = 'b.publisher_id = ?';
            $params[] = (int) $filters['publisher_id'];
        }
        if (!empty($filters['genre_id'])) {
            $where[] = 'EXISTS (SELECT 1 FROM book_genres bg3 WHERE bg3.book_id = b.id AND bg3.genre_id = ?)';
            $params[] = (int) $filters['genre_id'];
        }
        if (!empty($filters['language'])) {
            $where[] = 'b.language = ?';
            $params[] = $filters['language'];
        }
        if (!empty($filters['year'])) {
            $where[] = 'b.publication_year = ?';
            $params[] = (int) $filters['year'];
        }
        if (!empty($filters['decade'])) {
            $decade = (int) $filters['decade'];
            $where[] = 'b.publication_year >= ? AND b.publication_year < ?';
            $params[] = $decade;
            $params[] = $decade + 10;
        }
        if (!empty($filters['series_id'])) {
            $where[] = 'b.series_id = ?';
            $params[] = (int) $filters['series_id'];
        }
        if (!empty($filters['collection_id'])) {
            $where[] = 'b.collection_id = ?';
            $params[] = (int) $filters['collection_id'];
        }
        if (!empty($filters['reading_status'])) {
            $where[] = 'b.reading_status = ?';
            $params[] = $filters['reading_status'];
        }
        if (!empty($filters['physical_condition'])) {
            $where[] = 'b.physical_condition = ?';
            $params[] = $filters['physical_condition'];
        }
        if (!empty($filters['format'])) {
            $where[] = 'b.format = ?';
            $params[] = $filters['format'];
        }
        if (($filters['has_isbn'] ?? '') === '1') {
            $where[] = '((b.isbn10 IS NOT NULL AND b.isbn10 <> "") OR (b.isbn13 IS NOT NULL AND b.isbn13 <> ""))';
        } elseif (($filters['has_isbn'] ?? '') === '0') {
            $where[] = '((b.isbn10 IS NULL OR b.isbn10 = "") AND (b.isbn13 IS NULL OR b.isbn13 = ""))';
        }
        if (($filters['has_cover'] ?? '') === '1') {
            $where[] = '(b.cover IS NOT NULL AND b.cover <> "")';
        } elseif (($filters['has_cover'] ?? '') === '0') {
            $where[] = '(b.cover IS NULL OR b.cover = "")';
        }

        $allowedSort = [
            'title' => 'b.title',
            'year' => 'b.publication_year',
            'pages' => 'b.pages',
            'status' => 'b.reading_status',
            'author' => 'author_names',
            'publisher' => 'p.name',
            'created' => 'b.created_at',
            'id' => 'b.id',
        ];
        $sortCol = $allowedSort[$sort] ?? 'b.title';
        $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

        $whereSql = implode(' AND ', $where);
        $pdo = Database::pdo();

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM books b
            LEFT JOIN publishers p ON p.id = b.publisher_id
            LEFT JOIN collections c ON c.id = b.collection_id
            LEFT JOIN series s ON s.id = b.series_id
            WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT b.*, p.name AS publisher_name, c.name AS collection_name, s.name AS series_name,
            (SELECT GROUP_CONCAT(a.name ORDER BY ba.sort_order, a.name SEPARATOR ', ')
             FROM book_authors ba INNER JOIN authors a ON a.id = ba.author_id
             WHERE ba.book_id = b.id) AS author_names,
            (SELECT GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ', ')
             FROM book_genres bg INNER JOIN genres g ON g.id = bg.genre_id
             WHERE bg.book_id = b.id) AS genre_names
            FROM books b
            LEFT JOIN publishers p ON p.id = b.publisher_id
            LEFT JOIN collections c ON c.id = b.collection_id
            LEFT JOIN series s ON s.id = b.series_id
            WHERE {$whereSql}
            ORDER BY {$sortCol} {$dir}, b.title ASC
            LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'items' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function recent(int $limit = 8): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT b.*, 
                (SELECT GROUP_CONCAT(a.name ORDER BY ba.sort_order, a.name SEPARATOR ', ')
                 FROM book_authors ba INNER JOIN authors a ON a.id = ba.author_id
                 WHERE ba.book_id = b.id) AS author_names
             FROM books b
             ORDER BY b.created_at DESC, b.id DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO books (
                    work_id, title, subtitle, isbn10, isbn13, publisher_id, publication_date, publication_year,
                    edition, volume, collection_id, series_id, series_number, language, pages, format, description,
                    physical_condition, reading_status, cover, goodreads_url, purchase_date, purchase_price,
                    purchase_place, estimated_value, rating, reading_started_at, reading_finished_at, reading_comment, notes
                ) VALUES (
                    :work_id, :title, :subtitle, :isbn10, :isbn13, :publisher_id, :publication_date, :publication_year,
                    :edition, :volume, :collection_id, :series_id, :series_number, :language, :pages, :format, :description,
                    :physical_condition, :reading_status, :cover, :goodreads_url, :purchase_date, :purchase_price,
                    :purchase_place, :estimated_value, :rating, :reading_started_at, :reading_finished_at, :reading_comment, :notes
                )'
            );
            $stmt->execute(self::bindBook($data));
            $id = (int) $pdo->lastInsertId();
            self::syncRelations($id, $data);
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $fields = self::bindBook($data);
            $fields['id'] = $id;
            $stmt = $pdo->prepare(
                'UPDATE books SET
                    work_id=:work_id, title=:title, subtitle=:subtitle, isbn10=:isbn10, isbn13=:isbn13,
                    publisher_id=:publisher_id, publication_date=:publication_date, publication_year=:publication_year,
                    edition=:edition, volume=:volume, collection_id=:collection_id, series_id=:series_id,
                    series_number=:series_number, language=:language, pages=:pages, format=:format, description=:description,
                    physical_condition=:physical_condition, reading_status=:reading_status, cover=:cover,
                    goodreads_url=:goodreads_url, purchase_date=:purchase_date, purchase_price=:purchase_price,
                    purchase_place=:purchase_place, estimated_value=:estimated_value, rating=:rating,
                    reading_started_at=:reading_started_at, reading_finished_at=:reading_finished_at,
                    reading_comment=:reading_comment, notes=:notes
                 WHERE id=:id'
            );
            $stmt->execute($fields);
            self::syncRelations($id, $data);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function delete(int $id): void
    {
        $book = self::find($id);
        if ($book && !empty($book['cover']) && !str_starts_with((string) $book['cover'], 'http')) {
            $path = dirname(__DIR__, 2) . '/storage/covers/' . $book['cover'];
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $stmt = Database::pdo()->prepare('DELETE FROM books WHERE id = ?');
        $stmt->execute([$id]);
    }

    private static function bindBook(array $data): array
    {
        return [
            'work_id' => int_or_null($data['work_id'] ?? null),
            'title' => trim((string) ($data['title'] ?? '')),
            'subtitle' => null_if_blank($data['subtitle'] ?? null),
            'isbn10' => null_if_blank($data['isbn10'] ?? null),
            'isbn13' => null_if_blank($data['isbn13'] ?? null),
            'publisher_id' => int_or_null($data['publisher_id'] ?? null),
            'publication_date' => null_if_blank($data['publication_date'] ?? null),
            'publication_year' => int_or_null($data['publication_year'] ?? null),
            'edition' => null_if_blank($data['edition'] ?? null),
            'volume' => null_if_blank($data['volume'] ?? null),
            'collection_id' => int_or_null($data['collection_id'] ?? null),
            'series_id' => int_or_null($data['series_id'] ?? null),
            'series_number' => float_or_null($data['series_number'] ?? null),
            'language' => null_if_blank($data['language'] ?? null),
            'pages' => int_or_null($data['pages'] ?? null),
            'format' => null_if_blank($data['format'] ?? null),
            'description' => null_if_blank($data['description'] ?? null),
            'physical_condition' => $data['physical_condition'] ?? 'good',
            'reading_status' => $data['reading_status'] ?? 'unread',
            'cover' => null_if_blank($data['cover'] ?? null),
            'goodreads_url' => null_if_blank($data['goodreads_url'] ?? null),
            'purchase_date' => null_if_blank($data['purchase_date'] ?? null),
            'purchase_price' => float_or_null($data['purchase_price'] ?? null),
            'purchase_place' => null_if_blank($data['purchase_place'] ?? null),
            'estimated_value' => float_or_null($data['estimated_value'] ?? null),
            'rating' => int_or_null($data['rating'] ?? null),
            'reading_started_at' => null_if_blank($data['reading_started_at'] ?? null),
            'reading_finished_at' => null_if_blank($data['reading_finished_at'] ?? null),
            'reading_comment' => null_if_blank($data['reading_comment'] ?? null),
            'notes' => null_if_blank($data['notes'] ?? null),
        ];
    }

    private static function syncRelations(int $bookId, array $data): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM book_authors WHERE book_id = ?')->execute([$bookId]);
        $pdo->prepare('DELETE FROM book_genres WHERE book_id = ?')->execute([$bookId]);
        $pdo->prepare('DELETE FROM book_tags WHERE book_id = ?')->execute([$bookId]);

        $authors = self::splitList($data['authors_text'] ?? ($data['authors'] ?? ''));
        $i = 0;
        foreach ($authors as $name) {
            $authorId = AuthorService::findOrCreate($name);
            $pdo->prepare('INSERT INTO book_authors (book_id, author_id, sort_order) VALUES (?, ?, ?)')
                ->execute([$bookId, $authorId, $i++]);
        }

        foreach (self::splitList($data['genres_text'] ?? ($data['genres'] ?? '')) as $name) {
            $genreId = self::findOrCreateNamed('genres', $name);
            $pdo->prepare('INSERT IGNORE INTO book_genres (book_id, genre_id) VALUES (?, ?)')
                ->execute([$bookId, $genreId]);
        }

        foreach (self::splitList($data['tags_text'] ?? ($data['tags'] ?? '')) as $name) {
            $tagId = self::findOrCreateNamed('tags', $name);
            $pdo->prepare('INSERT IGNORE INTO book_tags (book_id, tag_id) VALUES (?, ?)')
                ->execute([$bookId, $tagId]);
        }

        if (!empty($data['publisher_name'])) {
            $pubId = self::findOrCreateNamed('publishers', (string) $data['publisher_name']);
            $pdo->prepare('UPDATE books SET publisher_id = ? WHERE id = ?')->execute([$pubId, $bookId]);
        }
        if (!empty($data['collection_name'])) {
            $colId = self::findOrCreateNamed('collections', (string) $data['collection_name']);
            $pdo->prepare('UPDATE books SET collection_id = ? WHERE id = ?')->execute([$colId, $bookId]);
        }
        if (!empty($data['series_name'])) {
            $serId = self::findOrCreateNamed('series', (string) $data['series_name']);
            $pdo->prepare('UPDATE books SET series_id = ? WHERE id = ?')->execute([$serId, $bookId]);
        }
    }

    public static function findOrCreateNamed(string $table, string $name): int
    {
        $name = trim($name);
        $allowed = ['genres', 'tags', 'publishers', 'collections', 'series'];
        if (!in_array($table, $allowed, true)) {
            throw new InvalidArgumentException('Invalid table');
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE name = ?");
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        $pdo->prepare("INSERT INTO {$table} (name) VALUES (?)")->execute([$name]);
        return (int) $pdo->lastInsertId();
    }

    public static function splitList(string|array $value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = preg_split('/[,;\/|]+/', $value) ?: [];
        }
        $out = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $out[] = $part;
            }
        }
        return array_values(array_unique($out));
    }

    public static function handleCoverUpload(?array $file, ?string $coverUrl = null, ?string $existing = null): ?string
    {
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                throw new RuntimeException('Formato de portada no soportado.');
            }
            $name = 'cover_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = dirname(__DIR__, 2) . '/storage/covers/' . $name;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                throw new RuntimeException('No se pudo guardar la portada.');
            }
            if ($existing && !str_starts_with($existing, 'http')) {
                $old = dirname(__DIR__, 2) . '/storage/covers/' . $existing;
                if (is_file($old)) {
                    @unlink($old);
                }
            }
            return $name;
        }
        $coverUrl = null_if_blank($coverUrl);
        if ($coverUrl !== null) {
            return $coverUrl;
        }
        return $existing;
    }

    public static function filterOptions(): array
    {
        $pdo = Database::pdo();
        return [
            'authors' => $pdo->query('SELECT id, name FROM authors ORDER BY name')->fetchAll(),
            'publishers' => $pdo->query('SELECT id, name FROM publishers ORDER BY name')->fetchAll(),
            'genres' => $pdo->query('SELECT id, name FROM genres ORDER BY name')->fetchAll(),
            'languages' => $pdo->query('SELECT DISTINCT language AS name FROM books WHERE language IS NOT NULL AND language <> "" ORDER BY language')->fetchAll(),
            'formats' => $pdo->query('SELECT DISTINCT format AS name FROM books WHERE format IS NOT NULL AND format <> "" ORDER BY format')->fetchAll(),
            'series' => $pdo->query('SELECT id, name FROM series ORDER BY name')->fetchAll(),
            'collections' => $pdo->query('SELECT id, name FROM collections ORDER BY name')->fetchAll(),
            'years' => $pdo->query('SELECT DISTINCT publication_year AS year FROM books WHERE publication_year IS NOT NULL ORDER BY publication_year DESC')->fetchAll(),
        ];
    }

    public static function globalSearch(string $q, int $limit = 20): array
    {
        if (trim($q) === '') {
            return [];
        }
        $result = self::search(['q' => $q], 'title', 'asc', 1, $limit);
        return $result['items'];
    }
}
