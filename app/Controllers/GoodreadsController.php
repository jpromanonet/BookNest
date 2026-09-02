<?php

declare(strict_types=1);

final class GoodreadsController
{
    public static function lookup(): void
    {
        require_csrf();
        try {
            $query = (string) ($_POST['query'] ?? '');
            $data = GoodreadsService::lookup($query);
            json_response(['ok' => true, 'book' => $data]);
        } catch (Throwable $e) {
            json_response(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public static function enrichNext(): void
    {
        require_csrf();
        try {
            $mode = (string) ($_POST['mode'] ?? 'missing');
            $all = $mode === 'all';
            $skip = [];
            if (!empty($_POST['skip_ids']) && is_array($_POST['skip_ids'])) {
                foreach ($_POST['skip_ids'] as $id) {
                    $skip[] = (int) $id;
                }
            }

            $books = GoodreadsService::booksNeedingEnrichment($all, 1, $skip);
            if (!$books) {
                json_response([
                    'ok' => true,
                    'done' => true,
                    'pending' => GoodreadsService::countNeedingEnrichment(),
                    'total' => BookService::countAll(),
                    'message' => 'Archive sync complete.',
                ]);
            }

            $book = $books[0];
            $meta = GoodreadsService::enrichBook($book);
            json_response([
                'ok' => true,
                'done' => false,
                'book_id' => (int) $book['id'],
                'title' => $book['title'],
                'pages' => $meta['pages'] ?? null,
                'pending' => GoodreadsService::countNeedingEnrichment(),
                'total' => BookService::countAll(),
                'message' => 'Updated: ' . $book['title'],
            ]);
        } catch (Throwable $e) {
            $failedId = isset($book) && is_array($book) ? (int) $book['id'] : null;
            json_response([
                'ok' => false,
                'done' => false,
                'book_id' => $failedId,
                'title' => isset($book['title']) ? (string) $book['title'] : null,
                'error' => $e->getMessage(),
                'pending' => GoodreadsService::countNeedingEnrichment(),
                'total' => BookService::countAll(),
            ], 422);
        }
    }
}
