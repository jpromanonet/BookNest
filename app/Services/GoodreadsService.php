<?php

declare(strict_types=1);

/**
 * Best-effort public Goodreads metadata lookup (no official API).
 * Results are editable before save.
 */
final class GoodreadsService
{
    public static function lookup(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            throw new RuntimeException('Ingresá un ISBN, título o URL de Goodreads.');
        }

        if (preg_match('#^https?://#i', $query)) {
            return self::parseBookPage($query);
        }

        $digits = preg_replace('/[\s-]/', '', $query) ?? '';
        if (preg_match('/^\d{10}(\d{3})?$/', $digits) || preg_match('/^\d{9}[\dXx]$/', $digits)) {
            $searchUrl = 'https://www.goodreads.com/search?q=' . rawurlencode($digits);
        } else {
            $searchUrl = 'https://www.goodreads.com/search?q=' . rawurlencode($query);
        }

        $html = self::fetch($searchUrl);
        $bookUrl = self::firstBookUrl($html);
        if ($bookUrl === null) {
            throw new RuntimeException('No se encontró el libro en los archivos de Goodreads.');
        }
        return self::parseBookPage($bookUrl);
    }

    private static function firstBookUrl(string $html): ?string
    {
        if (preg_match('#href="(/book/show/[^"]+)"#', $html, $m)) {
            return 'https://www.goodreads.com' . html_entity_decode($m[1]);
        }
        if (preg_match('#content="(https://www\.goodreads\.com/book/show/[^"]+)"#', $html, $m)) {
            return html_entity_decode($m[1]);
        }
        return null;
    }

    private static function parseBookPage(string $url): array
    {
        if (!str_contains($url, 'goodreads.com')) {
            throw new RuntimeException('La URL debe ser de Goodreads.');
        }
        $html = self::fetch($url);

        $title = self::meta($html, 'og:title') ?? self::between($html, '<h1', '</h1>');
        $title = trim(html_entity_decode(strip_tags((string) $title)));
        $title = preg_replace('/\s+/', ' ', $title) ?? $title;

        $description = self::meta($html, 'og:description') ?? '';
        $cover = self::meta($html, 'og:image');

        $authors = [];
        if (preg_match_all('#/author/show/[^"]+"[^>]*>([^<]+)</a>#', $html, $am)) {
            foreach ($am[1] as $name) {
                $name = trim(html_entity_decode($name));
                if ($name !== '' && !in_array($name, $authors, true)) {
                    $authors[] = $name;
                }
            }
        }

        $isbn13 = self::match($html, '/itemprop="isbn"[^>]*>(\d{13})</')
            ?? self::match($html, '/ISBN13<\/[^>]+>\s*<[^>]+>(\d{13})</i')
            ?? self::match($html, '/"isbn13"\s*:\s*"(\d{13})"/');
        $isbn10 = self::match($html, '/"isbn"\s*:\s*"([\dXx]{10})"/')
            ?? self::match($html, '/ISBN<\/[^>]+>\s*<[^>]+>([\dXx\-]{10,13})</i');

        $pages = self::match($html, '/itemprop="numberOfPages"[^>]*>(\d+)/')
            ?? self::match($html, '/(\d+)\s*pages/i');
        $year = self::match($html, '/First published[^<]*(\d{4})/i')
            ?? self::match($html, '/Published[^<]*(\d{4})/i')
            ?? self::match($html, '/"publicationYear"\s*:\s*(\d{4})/');

        $publisher = self::match($html, '/Published[^<]*by\s+([^<\(]+)/i');
        $language = self::match($html, '/Language[^<]*<[^>]+>([^<]+)</i');
        $format = self::match($html, '/Format[^<]*<[^>]+>([^<]+)</i')
            ?? self::match($html, '/(Hardcover|Paperback|Kindle|ebook|Mass Market)/i');

        $genres = [];
        if (preg_match_all('#/genres/[^"]+"[^>]*>([^<]+)</a>#', $html, $gm)) {
            foreach ($gm[1] as $g) {
                $g = trim(html_entity_decode($g));
                if ($g !== '' && !in_array($g, $genres, true)) {
                    $genres[] = $g;
                }
            }
            $genres = array_slice($genres, 0, 8);
        }

        $series = null;
        $seriesNumber = null;
        if (preg_match('#\(([^)]+)\s+#(\d+(?:\.\d+)?)\)#', $title, $sm)) {
            $series = trim($sm[1]);
            $seriesNumber = $sm[2];
            $title = trim(preg_replace('#\s*\([^)]+\)\s*$#', '', $title) ?? $title);
        }

        return [
            'title' => $title,
            'subtitle' => null,
            'authors' => $authors,
            'authors_text' => implode(', ', $authors),
            'isbn10' => $isbn10 ? preg_replace('/\D/', '', strtoupper($isbn10)) : null,
            'isbn13' => $isbn13,
            'publisher' => $publisher ? trim($publisher) : null,
            'publication_year' => $year ? (int) $year : null,
            'pages' => $pages ? (int) $pages : null,
            'language' => $language ? trim($language) : null,
            'format' => $format ? trim($format) : null,
            'series_name' => $series,
            'series_number' => $seriesNumber,
            'description' => trim(html_entity_decode($description)),
            'genres' => $genres,
            'genres_text' => implode(', ', $genres),
            'cover' => $cover,
            'goodreads_url' => $url,
            'source' => 'goodreads',
        ];
    }

    public static function booksNeedingEnrichment(bool $all = false, int $limit = 1, array $skipIds = []): array
    {
        $pdo = Database::pdo();
        $limit = max(1, min(100, $limit));
        $skipSql = '';
        $params = [];
        if ($skipIds) {
            $skipIds = array_values(array_unique(array_map('intval', $skipIds)));
            $placeholders = implode(',', array_fill(0, count($skipIds), '?'));
            $skipSql = " AND id NOT IN ({$placeholders})";
            $params = $skipIds;
        }

        if ($all) {
            $sql = "SELECT id, title, isbn10, isbn13, goodreads_url, pages FROM books WHERE 1=1{$skipSql} ORDER BY id ASC LIMIT {$limit}";
        } else {
            $sql = "SELECT id, title, isbn10, isbn13, goodreads_url, pages FROM books
                    WHERE (pages IS NULL OR pages = 0 OR goodreads_url IS NULL OR goodreads_url = '')
                    {$skipSql}
                    ORDER BY id ASC LIMIT {$limit}";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countNeedingEnrichment(): int
    {
        return (int) Database::pdo()->query(
            "SELECT COUNT(*) FROM books
             WHERE pages IS NULL OR pages = 0 OR goodreads_url IS NULL OR goodreads_url = ''"
        )->fetchColumn();
    }

    public static function enrichBook(array $book): array
    {
        $query = null_if_blank($book['goodreads_url'] ?? null)
            ?? null_if_blank($book['isbn13'] ?? null)
            ?? null_if_blank($book['isbn10'] ?? null)
            ?? trim((string) ($book['title'] ?? ''));

        if ($query === null || $query === '') {
            throw new RuntimeException('Sin datos suficientes para buscar en Goodreads.');
        }

        // Prefer title + first author when searching by title alone
        if (!preg_match('#^https?://#i', $query) && !preg_match('/^\d{9,13}/', preg_replace('/[\s-]/', '', $query) ?? '')) {
            $authors = BookService::authorsFor((int) $book['id']);
            if ($authors) {
                $query = $book['title'] . ' ' . $authors[0]['name'];
            }
        }

        $meta = self::lookup($query);
        self::applyMetadata((int) $book['id'], $meta, $book);
        return $meta;
    }

    public static function applyMetadata(int $bookId, array $meta, ?array $existing = null): void
    {
        $existing ??= BookService::find($bookId) ?: [];
        $data = [
            'title' => $existing['title'] ?? ($meta['title'] ?? ''),
            'subtitle' => $existing['subtitle'] ?: ($meta['subtitle'] ?? null),
            'authors_text' => ($existing['authors_text'] ?? '') !== ''
                ? $existing['authors_text']
                : ($meta['authors_text'] ?? ''),
            'isbn10' => $existing['isbn10'] ?: ($meta['isbn10'] ?? null),
            'isbn13' => $existing['isbn13'] ?: ($meta['isbn13'] ?? null),
            'publisher_name' => ($existing['publisher_name'] ?? null) ?: ($meta['publisher'] ?? null),
            'publication_year' => $existing['publication_year'] ?: ($meta['publication_year'] ?? null),
            'publication_date' => $existing['publication_date'] ?? null,
            'edition' => $existing['edition'] ?? null,
            'volume' => $existing['volume'] ?? null,
            'collection_name' => $existing['collection_name'] ?? null,
            'series_name' => ($existing['series_name'] ?? null) ?: ($meta['series_name'] ?? null),
            'series_number' => $existing['series_number'] ?: ($meta['series_number'] ?? null),
            'language' => $existing['language'] ?: ($meta['language'] ?? null),
            'pages' => $existing['pages'] ?: ($meta['pages'] ?? null),
            'format' => $existing['format'] ?: ($meta['format'] ?? null),
            'description' => $existing['description'] ?: ($meta['description'] ?? null),
            'physical_condition' => $existing['physical_condition'] ?? 'good',
            'reading_status' => $existing['reading_status'] ?? 'unread',
            'cover' => $existing['cover'] ?: ($meta['cover'] ?? null),
            'goodreads_url' => $existing['goodreads_url'] ?: ($meta['goodreads_url'] ?? null),
            'purchase_date' => $existing['purchase_date'] ?? null,
            'purchase_price' => $existing['purchase_price'] ?? null,
            'purchase_place' => $existing['purchase_place'] ?? null,
            'estimated_value' => $existing['estimated_value'] ?? null,
            'rating' => $existing['rating'] ?? null,
            'reading_started_at' => $existing['reading_started_at'] ?? null,
            'reading_finished_at' => $existing['reading_finished_at'] ?? null,
            'reading_comment' => $existing['reading_comment'] ?? null,
            'notes' => $existing['notes'] ?? null,
            'genres_text' => ($existing['genres_text'] ?? '') !== ''
                ? $existing['genres_text']
                : ($meta['genres_text'] ?? ''),
            'tags_text' => $existing['tags_text'] ?? '',
            'publisher_id' => $existing['publisher_id'] ?? null,
            'collection_id' => $existing['collection_id'] ?? null,
            'series_id' => $existing['series_id'] ?? null,
        ];
        BookService::update($bookId, $data);
    }

    private static function fetch(string $url): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_USERAGENT => 'BookNest/0.1 (+personal library; metadata lookup)',
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml',
                    'Accept-Language: en-US,en;q=0.9,es;q=0.8',
                ],
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($body === false || $code >= 400) {
                throw new RuntimeException('The archive could not be opened. ' . ($err ?: "HTTP {$code}"));
            }
            return (string) $body;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 20,
                'header' => "User-Agent: BookNest/0.1\r\nAccept: text/html\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            throw new RuntimeException('The archive could not be opened.');
        }
        return $body;
    }

    private static function meta(string $html, string $property): ?string
    {
        $prop = preg_quote($property, '/');
        if (preg_match('/property=["\']' . $prop . '["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) {
            return html_entity_decode($m[1]);
        }
        if (preg_match('/content=["\']([^"\']+)["\']\s+property=["\']' . $prop . '["\']/i', $html, $m)) {
            return html_entity_decode($m[1]);
        }
        return null;
    }

    private static function match(string $html, string $regex): ?string
    {
        if (preg_match($regex, $html, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private static function between(string $html, string $start, string $end): ?string
    {
        $p = stripos($html, $start);
        if ($p === false) {
            return null;
        }
        $p2 = stripos($html, $end, $p);
        if ($p2 === false) {
            return null;
        }
        return substr($html, $p, $p2 - $p);
    }
}
