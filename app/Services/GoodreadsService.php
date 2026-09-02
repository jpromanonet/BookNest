<?php

declare(strict_types=1);

/**
 * Best-effort public Goodreads metadata lookup (no official API).
 * Prefers JSON-LD embedded in public book pages.
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
            $searchUrl = 'https://www.goodreads.com/search?utf8=%E2%9C%93&q=' . rawurlencode($digits) . '&search_type=books';
            $html = self::fetch($searchUrl);
            $bookUrl = self::bestBookUrl($html, $query);
            if ($bookUrl === null) {
                throw new RuntimeException('No se encontró el libro en los archivos de Goodreads.');
            }
            return self::parseBookPage($bookUrl);
        }

        // Prefer title-only search; author is used to rank candidates.
        [$titleHint, $authorHint] = self::splitTitleAuthorHint($query);
        return self::lookupByTitleAuthor($titleHint, $authorHint !== '' ? $authorHint : null);
    }

    public static function lookupByTitleAuthor(string $title, ?string $author = null): array
    {
        $title = trim($title);
        $author = $author !== null ? trim($author) : '';
        if ($title === '') {
            throw new RuntimeException('Ingresá un título para buscar en Goodreads.');
        }

        $searchUrl = 'https://www.goodreads.com/search?utf8=%E2%9C%93&q=' . rawurlencode($title) . '&search_type=books';
        $html = self::fetch($searchUrl);
        $candidates = self::rankedBookUrls($html, $title, $author);
        if (!$candidates) {
            throw new RuntimeException('No se encontró el libro en los archivos de Goodreads.');
        }

        $fallback = null;
        $lastError = null;
        foreach (array_slice($candidates, 0, 5) as $bookUrl) {
            try {
                $meta = self::parseBookPage($bookUrl);
                $authors = $meta['authors'] ?? [];
                // Reject obvious junk authors unless nothing else works.
                $junk = self::isJunkAuthorSet($authors);
                if ($junk) {
                    $fallback = $fallback ?? $meta;
                    continue;
                }
                if ($author !== '' && !self::authorsMatchHint($authors, $author)) {
                    $fallback = $fallback ?? $meta;
                    continue;
                }
                return $meta;
            } catch (Throwable $e) {
                $lastError = $e;
            }
        }

        if (is_array($fallback)) {
            return $fallback;
        }
        if ($lastError) {
            throw $lastError;
        }
        throw new RuntimeException('No se encontró el libro en los archivos de Goodreads.');
    }

    private static function isJunkAuthorSet(array $authors): bool
    {
        if (!$authors) {
            return true;
        }
        foreach ($authors as $author) {
            $n = self::normName((string) $author);
            if (in_array($n, ['unknown author', 'sparknotes', 'cliffnotes', 'anonymous'], true)) {
                return true;
            }
        }
        return false;
    }

    private static function splitTitleAuthorHint(string $query): array
    {
        $query = trim($query);
        // "Title Author Name" — keep full query as title hint when short.
        if (preg_match('/^(.+?)\s+by\s+(.+)$/i', $query, $m)) {
            return [trim($m[1]), trim($m[2])];
        }
        return [$query, ''];
    }

    private static function authorsMatchHint(array $authors, string $hint): bool
    {
        if ($hint === '' || !$authors) {
            return true;
        }
        $hintNorm = self::normName($hint);
        foreach ($authors as $author) {
            $authorNorm = self::normName((string) $author);
            if ($authorNorm === '' || $authorNorm === 'unknown author' || $authorNorm === 'sparknotes') {
                continue;
            }
            if (str_contains($authorNorm, $hintNorm) || str_contains($hintNorm, $authorNorm)) {
                return true;
            }
            // Compare last tokens (surnames)
            $aParts = preg_split('/\s+/', $authorNorm) ?: [];
            $hParts = preg_split('/\s+/', $hintNorm) ?: [];
            if ($aParts && $hParts && end($aParts) === end($hParts)) {
                return true;
            }
        }
        return false;
    }

    private static function normName(string $value): string
    {
        $value = strtolower(trim(html_entity_decode($value)));
        $value = preg_replace('/[^a-z0-9áéíóúñü\s]/u', '', $value) ?? $value;
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private static function bestBookUrl(string $html, string $query = ''): ?string
    {
        $ranked = self::rankedBookUrls($html, $query, '');
        return $ranked[0] ?? null;
    }

    /** @return list<string> */
    private static function rankedBookUrls(string $html, string $titleHint, string $authorHint = ''): array
    {
        $items = [];
        if (preg_match_all('#class="[^"]*bookTitle[^"]*"[^>]*href="(/book/show/[^"]+)"[^>]*>(.*?)</a>#si', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $row) {
                $path = html_entity_decode($row[1]);
                $path = strtok($path, '?') ?: $path;
                $text = trim(html_entity_decode(strip_tags($row[2])));
                $items[] = ['path' => $path, 'text' => $text];
            }
        } elseif (preg_match_all('#href="(/book/show/[^"]+)"#', $html, $matches)) {
            foreach ($matches[1] as $path) {
                $path = html_entity_decode($path);
                $path = strtok($path, '?') ?: $path;
                $items[] = ['path' => $path, 'text' => $path];
            }
        }

        if (!$items) {
            return [];
        }

        $skip = '/sparknotes|cliffnotes|cliffsnotes|summary|study.?guide|workbook|companion|book.?analysis|essay|brightsummaries|gu[ií]a de lectura|detailed summary|critical companion|graphic novel|audible original/i';
        $titleNorm = self::normName($titleHint);
        $scored = [];

        foreach ($items as $i => $item) {
            $text = $item['text'];
            $path = $item['path'];
            $textNorm = self::normName($text);
            $score = 100 - $i; // prefer earlier ranks mildly

            if (preg_match($skip, $text) || preg_match($skip, $path)) {
                $score -= 1000;
            }
            if (preg_match('/unknown author/i', $text)) {
                $score -= 200;
            }
            if ($titleNorm !== '') {
                if ($textNorm === $titleNorm) {
                    $score += 500;
                } elseif (str_starts_with($textNorm, $titleNorm)) {
                    $score += 250;
                } elseif (str_contains($textNorm, $titleNorm)) {
                    $score += 80;
                }
            }
            // Prefer compact titles (actual books over long commentary titles)
            $score -= min(120, max(0, strlen($text) - 40));
            if ($authorHint !== '' && str_contains(self::normName($text . ' ' . $path), self::normName($authorHint))) {
                $score += 40;
            }

            $scored[] = ['score' => $score, 'url' => 'https://www.goodreads.com' . $path];
        }

        usort($scored, static fn ($a, $b) => $b['score'] <=> $a['score']);
        $urls = [];
        foreach ($scored as $row) {
            if ($row['score'] < -500) {
                continue;
            }
            if (!in_array($row['url'], $urls, true)) {
                $urls[] = $row['url'];
            }
        }
        return $urls;
    }

    private static function parseBookPage(string $url): array
    {
        if (!str_contains($url, 'goodreads.com')) {
            throw new RuntimeException('La URL debe ser de Goodreads.');
        }
        $html = self::fetch($url);
        $ld = self::jsonLdBook($html);

        $title = null;
        $authors = [];
        $language = null;
        $pages = null;
        $isbn13 = null;
        $isbn10 = null;
        $description = null;
        $cover = null;
        $publisher = null;
        $year = null;
        $format = null;

        if (is_array($ld)) {
            $title = isset($ld['name']) ? trim((string) $ld['name']) : null;
            $description = isset($ld['description']) ? trim(html_entity_decode(strip_tags((string) $ld['description']))) : null;
            $language = isset($ld['inLanguage']) ? self::normalizeLanguage((string) $ld['inLanguage']) : null;
            if (isset($ld['numberOfPages']) && is_numeric($ld['numberOfPages'])) {
                $pages = (int) $ld['numberOfPages'];
            }
            if (!empty($ld['isbn'])) {
                $isbnRaw = preg_replace('/[\s-]/', '', (string) $ld['isbn']) ?? '';
                if (strlen($isbnRaw) === 13) {
                    $isbn13 = $isbnRaw;
                } elseif (strlen($isbnRaw) === 10) {
                    $isbn10 = strtoupper($isbnRaw);
                }
            }
            if (!empty($ld['image'])) {
                $cover = is_array($ld['image']) ? ($ld['image'][0] ?? null) : (string) $ld['image'];
            }
            if (!empty($ld['author'])) {
                $authorNodes = $ld['author'];
                if (isset($authorNodes['name'])) {
                    $authorNodes = [$authorNodes];
                }
                if (is_array($authorNodes)) {
                    foreach ($authorNodes as $author) {
                        $name = trim((string) ($author['name'] ?? ''));
                        if ($name !== '' && !in_array($name, $authors, true)) {
                            $authors[] = $name;
                        }
                    }
                }
            }
            if (!empty($ld['publisher'])) {
                if (is_array($ld['publisher'])) {
                    $publisher = trim((string) ($ld['publisher']['name'] ?? ''));
                } else {
                    $publisher = trim((string) $ld['publisher']);
                }
                $publisher = $publisher !== '' ? $publisher : null;
            }
            if (!empty($ld['datePublished']) && preg_match('/(\d{4})/', (string) $ld['datePublished'], $ym)) {
                $year = (int) $ym[1];
            }
            if (!empty($ld['bookFormat'])) {
                $format = trim((string) $ld['bookFormat']);
            }
        }

        // Fallbacks from HTML / meta / NEXT_DATA
        $title = $title
            ?: self::meta($html, 'og:title')
            ?: trim(html_entity_decode(strip_tags((string) self::between($html, '<h1', '</h1>'))));
        $title = preg_replace('/\s+/', ' ', trim((string) $title)) ?? '';

        if ($description === null || $description === '') {
            $description = self::meta($html, 'og:description') ?? '';
            $description = trim(html_entity_decode($description));
        }
        $cover = $cover ?: self::meta($html, 'og:image');

        if (!$authors) {
            $authors = self::extractAuthorsFromHtml($html);
        }
        if (!$authors) {
            $authors = self::extractAuthorsFromNextData($html);
        }

        $isbn13 = $isbn13
            ?? self::match($html, '/"isbn13"\s*:\s*"(\d{13})"/')
            ?? self::match($html, '/itemprop="isbn"[^>]*>(\d{13})</');
        $isbn10 = $isbn10
            ?? self::match($html, '/"isbn"\s*:\s*"([\dXx]{10})"/')
            ?? self::match($html, '/"isbn10"\s*:\s*"([\dXx]{10})"/');

        $pages = $pages
            ?? int_or_null(self::match($html, '/"numberOfPages"\s*:\s*(\d+)/'))
            ?? int_or_null(self::match($html, '/itemprop="numberOfPages"[^>]*>(\d+)/'))
            ?? int_or_null(self::match($html, '/(\d+)\s*pages/i'));

        $year = $year
            ?? int_or_null(self::match($html, '/"publicationYear"\s*:\s*(\d{4})/'))
            ?? int_or_null(self::match($html, '/First published[^<]*(\d{4})/i'))
            ?? int_or_null(self::match($html, '/Published[^<\d]*(\d{4})/i'));

        if ($language === null) {
            $language = self::normalizeLanguage(
                self::match($html, '/"language"\s*:\s*\{[^}]*"name"\s*:\s*"([^"]+)"/')
                ?? self::match($html, '/"inLanguage"\s*:\s*"([^"]+)"/')
                ?? self::match($html, '/Language\s*<\/dt>\s*<dd[^>]*>([^<]+)/i')
                ?? self::match($html, '/Language[^A-Za-z]{0,40}(English|Spanish|Español|French|Français|German|Deutsch|Italian|Portuguese|Português|Japanese|Chinese|Russian)/i')
            );
        }

        if ($publisher === null) {
            $publisher = self::match($html, '/"publisher"\s*:\s*\{[^}]*"name"\s*:\s*"([^"]+)"/')
                ?? self::match($html, '/Published[^<]*by\s+([A-Z][^<\(]{1,80})/i');
            $publisher = $publisher ? trim($publisher) : null;
            // Avoid grabbing review text fragments
            if ($publisher && (strlen($publisher) > 80 || str_contains(strtolower($publisher), 'recommend'))) {
                $publisher = null;
            }
        }

        $format = $format
            ?: self::match($html, '/"format"\s*:\s*"([^"]+)"/')
            ?: self::match($html, '/(Hardcover|Paperback|Kindle Edition|ebook|Mass Market Paperback|Audiobook)/i');

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
        if (preg_match('/\(([^)]+?)\s+#(\d+(?:\.\d+)?)\)/', $title, $sm)) {
            $series = trim($sm[1]);
            $seriesNumber = $sm[2];
            $title = trim(preg_replace('/\s*\([^)]*#[^)]*\)\s*$/', '', $title) ?? $title);
        }

        // Clean "Title by Author" og titles sometimes
        if ($authors && preg_match('/^(.+?)\s+by\s+' . preg_quote($authors[0], '/') . '$/i', $title, $tm)) {
            $title = trim($tm[1]);
        }

        return [
            'title' => $title,
            'subtitle' => null,
            'authors' => $authors,
            'authors_text' => implode(', ', $authors),
            'isbn10' => $isbn10 ? preg_replace('/[^0-9Xx]/', '', strtoupper($isbn10)) : null,
            'isbn13' => $isbn13,
            'publisher' => $publisher,
            'publication_year' => $year,
            'pages' => $pages,
            'language' => $language,
            'format' => $format ? trim($format) : null,
            'series_name' => $series,
            'series_number' => $seriesNumber,
            'description' => $description ?: null,
            'genres' => $genres,
            'genres_text' => implode(', ', $genres),
            'cover' => $cover,
            'goodreads_url' => self::canonicalBookUrl($url),
            'source' => 'goodreads',
        ];
    }

    private static function canonicalBookUrl(string $url): string
    {
        $url = strtok($url, '?') ?: $url;
        $url = preg_replace('#/reviews/?$#', '', $url) ?? $url;
        return rtrim($url, '/');
    }

    private static function jsonLdBook(string $html): ?array
    {
        if (!preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#si', $html, $matches)) {
            return null;
        }
        foreach ($matches[1] as $json) {
            $data = json_decode(html_entity_decode(trim($json)), true);
            if (!is_array($data)) {
                continue;
            }
            $nodes = isset($data['@graph']) && is_array($data['@graph']) ? $data['@graph'] : [$data];
            foreach ($nodes as $node) {
                if (!is_array($node)) {
                    continue;
                }
                $type = $node['@type'] ?? null;
                if ($type === 'Book' || (is_array($type) && in_array('Book', $type, true))) {
                    return $node;
                }
            }
        }
        return null;
    }

    private static function extractAuthorsFromHtml(string $html): array
    {
        $authors = [];
        $patterns = [
            '#ContributorLink__name[^>]*>([^<]+)</#',
            '#/author/show/[^"]+"[^>]*>([^<]+)</a>#',
            '#itemprop="author"[^>]*>.*?<span[^>]*itemprop="name"[^>]*>([^<]+)</span>#si',
        ];
        foreach ($patterns as $pattern) {
            if (!preg_match_all($pattern, $html, $am)) {
                continue;
            }
            foreach ($am[1] as $name) {
                $name = trim(html_entity_decode(strip_tags($name)));
                if ($name === '' || strcasecmp($name, 'more') === 0) {
                    continue;
                }
                if (!in_array($name, $authors, true)) {
                    $authors[] = $name;
                }
            }
            if ($authors) {
                break;
            }
        }
        return array_slice($authors, 0, 8);
    }

    private static function extractAuthorsFromNextData(string $html): array
    {
        if (!preg_match('#<script id="__NEXT_DATA__"[^>]*>(.*?)</script>#s', $html, $m)) {
            return [];
        }
        $data = json_decode($m[1], true);
        if (!is_array($data)) {
            return [];
        }
        $json = json_encode($data);
        if (!is_string($json)) {
            return [];
        }
        $authors = [];
        if (preg_match_all('/"__typename"\s*:\s*"Contributor".*?"name"\s*:\s*"([^"]+)"/s', $json, $am)) {
            foreach ($am[1] as $name) {
                $name = trim(html_entity_decode($name));
                if ($name !== '' && !in_array($name, $authors, true)) {
                    $authors[] = $name;
                }
            }
        }
        return array_slice($authors, 0, 8);
    }

    private static function normalizeLanguage(?string $language): ?string
    {
        if ($language === null) {
            return null;
        }
        $language = trim(html_entity_decode($language));
        if ($language === '') {
            return null;
        }
        $map = [
            'en' => 'English',
            'eng' => 'English',
            'english' => 'English',
            'es' => 'Español',
            'spa' => 'Español',
            'spanish' => 'Español',
            'español' => 'Español',
            'fr' => 'Français',
            'fre' => 'Français',
            'french' => 'Français',
            'de' => 'Deutsch',
            'ger' => 'Deutsch',
            'german' => 'Deutsch',
            'it' => 'Italiano',
            'ita' => 'Italiano',
            'italian' => 'Italiano',
            'pt' => 'Português',
            'por' => 'Português',
            'portuguese' => 'Português',
        ];
        $key = strtolower($language);
        return $map[$key] ?? $language;
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
                    WHERE (
                        pages IS NULL OR pages = 0
                        OR goodreads_url IS NULL OR goodreads_url = ''
                        OR language IS NULL OR language = ''
                        OR NOT EXISTS (SELECT 1 FROM book_authors ba WHERE ba.book_id = books.id)
                    )
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
             WHERE pages IS NULL OR pages = 0
                OR goodreads_url IS NULL OR goodreads_url = ''
                OR language IS NULL OR language = ''
                OR NOT EXISTS (SELECT 1 FROM book_authors ba WHERE ba.book_id = books.id)"
        )->fetchColumn();
    }

    public static function enrichBook(array $book): array
    {
        $full = BookService::find((int) $book['id']);
        if (!$full) {
            throw new RuntimeException('Libro no encontrado.');
        }

        $isbn = null_if_blank($full['isbn13'] ?? null) ?? null_if_blank($full['isbn10'] ?? null);
        $url = null_if_blank($full['goodreads_url'] ?? null);
        $authorNames = array_column($full['authors'] ?? [], 'name');
        $authorName = null;
        if ($authorNames && !self::isJunkAuthorSet($authorNames)) {
            $authorName = $authorNames[0];
        }

        $meta = null;
        if ($isbn !== null) {
            try {
                $meta = self::lookup($isbn);
            } catch (Throwable $e) {
                $meta = null;
            }
        }

        if ($meta === null && $url !== null) {
            try {
                $meta = self::parseBookPage($url);
            } catch (Throwable $e) {
                $meta = null;
            }
        }

        $needsTitleSearch = $meta === null
            || self::isJunkAuthorSet($meta['authors'] ?? [])
            || empty($meta['language'])
            || empty($meta['pages'])
            || self::isJunkAuthorSet($authorNames);

        if ($needsTitleSearch) {
            try {
                $researched = self::lookupByTitleAuthor((string) ($full['title'] ?? ''), $authorName);
                if ($meta === null || !self::isJunkAuthorSet($researched['authors'] ?? [])) {
                    $meta = $researched;
                }
            } catch (Throwable $e) {
                if ($meta === null) {
                    throw $e;
                }
            }
        }

        if ($meta === null) {
            throw new RuntimeException('No se encontró el libro en los archivos de Goodreads.');
        }

        self::applyMetadata((int) $full['id'], $meta, $full);
        return $meta;
    }

    public static function applyMetadata(int $bookId, array $meta, ?array $existing = null): void
    {
        // Always reload full record so we never wipe authors/relations with a partial row.
        $existing = BookService::find($bookId) ?: ($existing ?? []);

        $existingAuthors = trim((string) ($existing['authors_text'] ?? ''));
        $metaAuthors = trim((string) ($meta['authors_text'] ?? ''));
        $existingAuthorList = $existingAuthors !== '' ? BookService::splitList($existingAuthors) : [];
        $metaAuthorList = $metaAuthors !== '' ? BookService::splitList($metaAuthors) : [];

        if ($metaAuthors !== '' && !self::isJunkAuthorSet($metaAuthorList)) {
            $authorsText = $metaAuthors;
        } elseif ($existingAuthors !== '' && !self::isJunkAuthorSet($existingAuthorList)) {
            $authorsText = $existingAuthors;
        } else {
            $authorsText = $metaAuthors !== '' ? $metaAuthors : $existingAuthors;
        }

        // If meta looks better, allow replacing a previously wrong Goodreads URL / ISBN.
        $goodreadsUrl = self::prefer($existing['goodreads_url'] ?? null, $meta['goodreads_url'] ?? null);
        $isbn10 = self::prefer($existing['isbn10'] ?? null, $meta['isbn10'] ?? null);
        $isbn13 = self::prefer($existing['isbn13'] ?? null, $meta['isbn13'] ?? null);
        if (!empty($meta['goodreads_url']) && (
            self::isJunkAuthorSet($existingAuthorList)
            || empty($existing['language'])
            || empty($existing['pages'])
            || self::isJunkAuthorSet($metaAuthorList) === false
        )) {
            // Replace URL when local data was bad OR when we have solid meta authors.
            if (self::isJunkAuthorSet($existingAuthorList) || !self::isJunkAuthorSet($metaAuthorList)) {
                $goodreadsUrl = $meta['goodreads_url'];
            }
        }
        if (!self::isJunkAuthorSet($metaAuthorList)) {
            if (!empty($meta['isbn10'])) {
                $isbn10 = $meta['isbn10'];
            }
            if (!empty($meta['isbn13'])) {
                $isbn13 = $meta['isbn13'];
            }
            if (!empty($meta['pages'])) {
                // Replace SparkNotes-ish short page counts when we found a real edition.
                $existingPages = (int) ($existing['pages'] ?? 0);
                if ($existingPages <= 0 || $existingPages < 100 || self::isJunkAuthorSet($existingAuthorList)) {
                    // handled below via prefer override
                }
            }
        }

        $data = [
            'title' => $existing['title'] ?? ($meta['title'] ?? ''),
            'subtitle' => self::prefer($existing['subtitle'] ?? null, $meta['subtitle'] ?? null),
            'authors_text' => $authorsText,
            'isbn10' => $isbn10,
            'isbn13' => $isbn13,
            'publisher_name' => self::prefer($existing['publisher_name'] ?? null, $meta['publisher'] ?? null),
            'publication_year' => self::prefer($existing['publication_year'] ?? null, $meta['publication_year'] ?? null),
            'publication_date' => $existing['publication_date'] ?? null,
            'edition' => $existing['edition'] ?? null,
            'volume' => $existing['volume'] ?? null,
            'collection_name' => $existing['collection_name'] ?? null,
            'series_name' => self::prefer($existing['series_name'] ?? null, $meta['series_name'] ?? null),
            'series_number' => self::prefer($existing['series_number'] ?? null, $meta['series_number'] ?? null),
            'language' => self::prefer($existing['language'] ?? null, $meta['language'] ?? null),
            'pages' => self::prefer($existing['pages'] ?? null, $meta['pages'] ?? null),
            'format' => self::prefer($existing['format'] ?? null, $meta['format'] ?? null),
            'description' => self::prefer($existing['description'] ?? null, $meta['description'] ?? null),
            'physical_condition' => $existing['physical_condition'] ?? 'good',
            'reading_status' => $existing['reading_status'] ?? 'unread',
            'cover' => self::prefer($existing['cover'] ?? null, $meta['cover'] ?? null),
            'goodreads_url' => $goodreadsUrl,
            'purchase_date' => $existing['purchase_date'] ?? null,
            'purchase_price' => $existing['purchase_price'] ?? null,
            'purchase_place' => $existing['purchase_place'] ?? null,
            'estimated_value' => $existing['estimated_value'] ?? null,
            'rating' => $existing['rating'] ?? null,
            'reading_started_at' => $existing['reading_started_at'] ?? null,
            'reading_finished_at' => $existing['reading_finished_at'] ?? null,
            'reading_comment' => $existing['reading_comment'] ?? null,
            'notes' => $existing['notes'] ?? null,
            'genres_text' => self::prefer($existing['genres_text'] ?? null, $meta['genres_text'] ?? null) ?? '',
            'tags_text' => $existing['tags_text'] ?? '',
            'publisher_id' => $existing['publisher_id'] ?? null,
            'collection_id' => $existing['collection_id'] ?? null,
            'series_id' => $existing['series_id'] ?? null,
        ];

        // If previous edition was junk/study-guide, force overwrite with healthier meta.
        if (!self::isJunkAuthorSet($metaAuthorList) && (
            self::isJunkAuthorSet($existingAuthorList)
            || (int) ($existing['pages'] ?? 0) < 100
        )) {
            $data['authors_text'] = $metaAuthors;
            $data['pages'] = $meta['pages'] ?? $data['pages'];
            $data['language'] = $meta['language'] ?: $data['language'];
            $data['cover'] = $meta['cover'] ?: $data['cover'];
            $data['description'] = $meta['description'] ?: $data['description'];
            $data['goodreads_url'] = $meta['goodreads_url'] ?: $data['goodreads_url'];
            $data['isbn10'] = $meta['isbn10'] ?: null;
            $data['isbn13'] = $meta['isbn13'] ?: null;
            $data['publication_year'] = $meta['publication_year'] ?: $data['publication_year'];
            $data['publisher_name'] = $meta['publisher'] ?: $data['publisher_name'];
            $data['genres_text'] = $meta['genres_text'] ?: $data['genres_text'];
        }
        BookService::update($bookId, $data);
    }

    private static function prefer(mixed $current, mixed $incoming): mixed
    {
        if ($current === null || $current === '' || $current === 0 || $current === '0') {
            return $incoming;
        }
        return $current;
    }

    private static function fetch(string $url): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
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
                'timeout' => 25,
                'header' => "User-Agent: Mozilla/5.0\r\nAccept: text/html\r\n",
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
