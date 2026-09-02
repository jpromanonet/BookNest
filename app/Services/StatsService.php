<?php

declare(strict_types=1);

final class StatsService
{
    public static function dashboard(): array
    {
        $pdo = Database::pdo();
        $totals = $pdo->query(
            "SELECT
                COUNT(*) AS books,
                COALESCE(SUM(pages), 0) AS pages,
                SUM(CASE WHEN reading_status = 'read' THEN 1 ELSE 0 END) AS read_count,
                SUM(CASE WHEN reading_status = 'unread' THEN 1 ELSE 0 END) AS unread_count,
                SUM(CASE WHEN reading_status = 'reading' THEN 1 ELSE 0 END) AS reading_count,
                SUM(CASE WHEN reading_status = 'abandoned' THEN 1 ELSE 0 END) AS abandoned_count,
                SUM(CASE WHEN reading_status = 'reread' THEN 1 ELSE 0 END) AS reread_count,
                COALESCE(SUM(estimated_value), 0) AS estimated_value,
                COALESCE(AVG(purchase_price), 0) AS avg_price,
                SUM(CASE WHEN (isbn10 IS NULL OR isbn10='') AND (isbn13 IS NULL OR isbn13='') THEN 1 ELSE 0 END) AS without_isbn,
                SUM(CASE WHEN cover IS NULL OR cover='' THEN 1 ELSE 0 END) AS without_cover,
                SUM(CASE WHEN reading_status IN ('read','reread') THEN COALESCE(pages,0) ELSE 0 END) AS pages_read,
                SUM(CASE WHEN reading_status IN ('unread','reading','abandoned') THEN COALESCE(pages,0) ELSE 0 END) AS pages_pending
             FROM books"
        )->fetch() ?: [];

        $authors = (int) $pdo->query('SELECT COUNT(*) FROM authors')->fetchColumn();
        $genres = (int) $pdo->query('SELECT COUNT(*) FROM genres')->fetchColumn();
        $publishers = (int) $pdo->query('SELECT COUNT(*) FROM publishers')->fetchColumn();
        $collections = (int) $pdo->query('SELECT COUNT(*) FROM collections')->fetchColumn();
        $series = (int) $pdo->query('SELECT COUNT(*) FROM series')->fetchColumn();
        $wishlist = (int) $pdo->query('SELECT COUNT(*) FROM wishlist')->fetchColumn();

        $books = (int) ($totals['books'] ?? 0);
        $read = (int) ($totals['read_count'] ?? 0) + (int) ($totals['reread_count'] ?? 0);
        $progress = $books > 0 ? (int) round(($read / $books) * 100) : 0;

        return [
            'books' => $books,
            'pages' => (int) ($totals['pages'] ?? 0),
            'authors' => $authors,
            'genres' => $genres,
            'publishers' => $publishers,
            'collections' => $collections,
            'series' => $series,
            'wishlist' => $wishlist,
            'read' => (int) ($totals['read_count'] ?? 0),
            'unread' => (int) ($totals['unread_count'] ?? 0),
            'reading' => (int) ($totals['reading_count'] ?? 0),
            'abandoned' => (int) ($totals['abandoned_count'] ?? 0),
            'reread' => (int) ($totals['reread_count'] ?? 0),
            'estimated_value' => (float) ($totals['estimated_value'] ?? 0),
            'avg_price' => (float) ($totals['avg_price'] ?? 0),
            'without_isbn' => (int) ($totals['without_isbn'] ?? 0),
            'without_cover' => (int) ($totals['without_cover'] ?? 0),
            'pages_read' => (int) ($totals['pages_read'] ?? 0),
            'pages_pending' => (int) ($totals['pages_pending'] ?? 0),
            'avg_pages' => $books > 0 ? (int) round(((int) ($totals['pages'] ?? 0)) / $books) : 0,
            'progress' => $progress,
            'by_genre' => self::groupCount(
                "SELECT g.name AS label, COUNT(*) AS total
                 FROM book_genres bg INNER JOIN genres g ON g.id = bg.genre_id
                 GROUP BY g.id, g.name ORDER BY total DESC LIMIT 12"
            ),
            'by_language' => self::groupCount(
                "SELECT label, COUNT(*) AS total
                 FROM (
                     SELECT COALESCE(NULLIF(language, ''), 'Sin idioma') AS label
                     FROM books
                 ) AS languages
                 GROUP BY label
                 ORDER BY total DESC
                 LIMIT 12"
            ),
            'by_status' => self::groupCount(
                "SELECT reading_status AS label, COUNT(*) AS total FROM books GROUP BY reading_status"
            ),
            'by_decade' => self::groupCount(
                "SELECT CONCAT(decade, 's') AS label, COUNT(*) AS total
                 FROM (
                     SELECT (FLOOR(publication_year / 10) * 10) AS decade
                     FROM books
                     WHERE publication_year IS NOT NULL
                 ) AS decades
                 GROUP BY decade
                 ORDER BY decade"
            ),
            'top_authors' => self::groupCount(
                "SELECT a.name AS label, COUNT(*) AS total
                 FROM book_authors ba INNER JOIN authors a ON a.id = ba.author_id
                 GROUP BY a.id, a.name ORDER BY total DESC LIMIT 10"
            ),
            'top_publishers' => self::groupCount(
                "SELECT p.name AS label, COUNT(*) AS total
                 FROM books b INNER JOIN publishers p ON p.id = b.publisher_id
                 GROUP BY p.id, p.name ORDER BY total DESC LIMIT 10"
            ),
            'by_year_added' => self::groupCount(
                "SELECT year_added AS label, COUNT(*) AS total
                 FROM (
                     SELECT YEAR(created_at) AS year_added
                     FROM books
                 ) AS years
                 GROUP BY year_added
                 ORDER BY year_added"
            ),
        ];
    }

    private static function groupCount(string $sql): array
    {
        return Database::pdo()->query($sql)->fetchAll();
    }
}
