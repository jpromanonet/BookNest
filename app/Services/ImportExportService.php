<?php

declare(strict_types=1);

final class ImportExportService
{
    public static function exportJson(): array
    {
        $pdo = Database::pdo();
        $books = $pdo->query('SELECT * FROM books ORDER BY id')->fetchAll();
        $out = [];
        foreach ($books as $book) {
            $id = (int) $book['id'];
            $book['authors'] = array_column(BookService::authorsFor($id), 'name');
            $book['genres'] = array_column(BookService::genresFor($id), 'name');
            $book['tags'] = array_column(BookService::tagsFor($id), 'name');
            $book['publisher'] = null;
            if ($book['publisher_id']) {
                $stmt = $pdo->prepare('SELECT name FROM publishers WHERE id = ?');
                $stmt->execute([$book['publisher_id']]);
                $book['publisher'] = $stmt->fetchColumn() ?: null;
            }
            $book['collection'] = null;
            if ($book['collection_id']) {
                $stmt = $pdo->prepare('SELECT name FROM collections WHERE id = ?');
                $stmt->execute([$book['collection_id']]);
                $book['collection'] = $stmt->fetchColumn() ?: null;
            }
            $book['series'] = null;
            if ($book['series_id']) {
                $stmt = $pdo->prepare('SELECT name FROM series WHERE id = ?');
                $stmt->execute([$book['series_id']]);
                $book['series'] = $stmt->fetchColumn() ?: null;
            }
            unset($book['publisher_id'], $book['collection_id'], $book['series_id'], $book['work_id']);
            $out[] = $book;
        }
        return [
            'exported_at' => date('c'),
            'version' => app_config('version'),
            'count' => count($out),
            'books' => $out,
            'authors' => $pdo->query('SELECT * FROM authors ORDER BY name')->fetchAll(),
            'wishlist' => $pdo->query('SELECT * FROM wishlist ORDER BY id')->fetchAll(),
        ];
    }

    public static function exportCsv(): string
    {
        $data = self::exportJson();
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, [
            'id', 'title', 'subtitle', 'authors', 'isbn10', 'isbn13', 'publisher', 'publication_year',
            'edition', 'volume', 'collection', 'series', 'series_number', 'language', 'pages', 'format',
            'genres', 'tags', 'physical_condition', 'reading_status', 'purchase_price', 'estimated_value', 'notes',
        ]);
        foreach ($data['books'] as $b) {
            fputcsv($fh, [
                $b['id'], $b['title'], $b['subtitle'], implode('; ', $b['authors'] ?? []),
                $b['isbn10'], $b['isbn13'], $b['publisher'], $b['publication_year'],
                $b['edition'], $b['volume'], $b['collection'], $b['series'], $b['series_number'],
                $b['language'], $b['pages'], $b['format'],
                implode('; ', $b['genres'] ?? []), implode('; ', $b['tags'] ?? []),
                $b['physical_condition'], $b['reading_status'], $b['purchase_price'], $b['estimated_value'], $b['notes'],
            ]);
        }
        rewind($fh);
        $csv = stream_get_contents($fh) ?: '';
        fclose($fh);
        return $csv;
    }

    public static function importJson(string $json, bool $replace = false): array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException('JSON inválido.');
        }

        // Support both BookNest export format and simple [{id,titulo,autor}] seed
        $books = $data['books'] ?? $data;
        if (!is_array($books)) {
            throw new RuntimeException('No se encontraron libros en el JSON.');
        }

        $pdo = Database::pdo();
        if ($replace) {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach (['book_tags','book_genres','book_authors','reading_history','books','wishlist'] as $t) {
                $pdo->exec("TRUNCATE TABLE {$t}");
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }

        $imported = 0;
        foreach ($books as $row) {
            if (!is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? $row['titulo'] ?? ''));
            if ($title === '') {
                continue;
            }
            $authors = $row['authors'] ?? $row['autor'] ?? '';
            if (is_array($authors)) {
                $authors = implode(', ', $authors);
            }

            BookService::create([
                'title' => $title,
                'subtitle' => $row['subtitle'] ?? null,
                'authors_text' => (string) $authors,
                'isbn10' => $row['isbn10'] ?? null,
                'isbn13' => $row['isbn13'] ?? ($row['isbn'] ?? null),
                'publisher_name' => $row['publisher'] ?? null,
                'publication_date' => $row['publication_date'] ?? null,
                'publication_year' => $row['publication_year'] ?? ($row['year'] ?? null),
                'edition' => $row['edition'] ?? null,
                'volume' => $row['volume'] ?? null,
                'collection_name' => $row['collection'] ?? null,
                'series_name' => $row['series'] ?? null,
                'series_number' => $row['series_number'] ?? null,
                'language' => $row['language'] ?? null,
                'pages' => $row['pages'] ?? null,
                'format' => $row['format'] ?? null,
                'description' => $row['description'] ?? null,
                'physical_condition' => $row['physical_condition'] ?? 'good',
                'reading_status' => $row['reading_status'] ?? 'unread',
                'cover' => $row['cover'] ?? null,
                'goodreads_url' => $row['goodreads_url'] ?? null,
                'purchase_date' => $row['purchase_date'] ?? null,
                'purchase_price' => $row['purchase_price'] ?? null,
                'purchase_place' => $row['purchase_place'] ?? null,
                'estimated_value' => $row['estimated_value'] ?? null,
                'rating' => $row['rating'] ?? null,
                'reading_started_at' => $row['reading_started_at'] ?? null,
                'reading_finished_at' => $row['reading_finished_at'] ?? null,
                'reading_comment' => $row['reading_comment'] ?? null,
                'notes' => $row['notes'] ?? null,
                'genres_text' => is_array($row['genres'] ?? null) ? implode(', ', $row['genres']) : ($row['genres'] ?? ''),
                'tags_text' => is_array($row['tags'] ?? null) ? implode(', ', $row['tags']) : ($row['tags'] ?? ''),
            ]);
            $imported++;
        }

        return ['imported' => $imported];
    }

    public static function importCsv(string $csv, bool $replace = false): array
    {
        $lines = preg_split('/\r\n|\n|\r/', trim($csv)) ?: [];
        if (count($lines) < 2) {
            throw new RuntimeException('CSV vacío.');
        }
        $header = str_getcsv(array_shift($lines));
        $header = array_map(static fn ($h) => strtolower(trim((string) $h)), $header);
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = str_getcsv($line);
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $cols[$i] ?? null;
            }
            if (!empty($row['authors'])) {
                $row['authors'] = preg_split('/[;|]/', (string) $row['authors']) ?: [];
            }
            if (!empty($row['genres'])) {
                $row['genres'] = preg_split('/[;|]/', (string) $row['genres']) ?: [];
            }
            if (!empty($row['tags'])) {
                $row['tags'] = preg_split('/[;|]/', (string) $row['tags']) ?: [];
            }
            $rows[] = $row;
        }
        return self::importJson(json_encode(['books' => $rows], JSON_UNESCAPED_UNICODE), $replace);
    }

    public static function importSeedFile(string $path, bool $replace = true): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('Seed file not found: ' . $path);
        }
        $json = (string) file_get_contents($path);
        return self::importJson($json, $replace);
    }
}
